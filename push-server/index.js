// WinChatty Server
// Copyright (c) 2013 Brian Luft
//
// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
// documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
// rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
// permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
// Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
// WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS
// OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
// OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

var express = require('express');
var fs = require('fs');
var app = express();
var httpProxy = require('http-proxy');
var proxy = httpProxy.createServer({ timeout: 660000 });

app.use(require('morgan')('combined'));
app.use(require('compression')({
   filter: function (req, res) { return true; },
   threshold: 1
}));

//var globalSTS = require('strict-transport-security').getSTS({"max-age": {days: 180}});
//app.use(globalSTS);

var TIMEOUT_MSEC = 10 * 60 * 1000;
var PRUNE_INTERVAL_MSEC = 1000;
var NOTIFICATION_INTERVAL_MSEC = 30000;
var EVENT_ID_FILE_PATH = '/mnt/ssd/ChattyIndex/LastEventID';
var EVENTS_FILE_PATH = '/mnt/ssd/ChattyIndex/LastEvents.json';

function isInt(value) {
   if (isNaN(value)) {
      return false;
   }
   var x = parseFloat(value);
   return (x | 0) === x;
}

function errorResponse(code, message) {
   return {
      "error": true,
      "code": code,
      "message": message
   };
}

app.get('/v2/waitForEvent', (function() {
   var g_lastEventId = 0;
   var g_lastEvents = [];
   var g_waitForEventConnections = [];

   function eventsResponse(lastEventId) {
      var events = [];
      for (var i = 0; i < g_lastEvents.length; i++) {
         if (g_lastEvents[i].eventId > lastEventId) {
            events.push(g_lastEvents[i]);
         }
      }
      if (events.length == g_lastEvents.length) {
         return errorResponse('ERR_TOO_MANY_EVENTS', 'Too many events.');
      } else {
         return { lastEventId: g_lastEventId, events: events };
      }
   }

   function processEventConnections(responseFunc) {
      for (var i = g_waitForEventConnections.length - 1; i >= 0; i--) {
         var connection = g_waitForEventConnections[i];
         var response = responseFunc(connection);
         if (response) {
            connection.res.send(response);
            g_waitForEventConnections.splice(i, 1);
         }
      }
   }

   function sendEvents() {
      processEventConnections(function (connection) {
         if (g_lastEventId > connection.lastEventId) {
            return eventsResponse(connection.lastEventId);
         } else {
            return null;
         }
      });
   }

   function pollEvents() {
      fs.readFile(EVENT_ID_FILE_PATH, function (eventIdError, eventIdData) {
         if (eventIdError) {
            console.log('Error reading event ID file: ' + eventIdError.message);
            return;
         }

         var lastEventId = parseInt(eventIdData);
         if (lastEventId > g_lastEventId) {
            fs.readFile(EVENTS_FILE_PATH, function (eventsError, eventsData) {
               if (eventsError) {
                  console.log('Error reading events file: ' + eventsError.message);
                  return;
               }

               try {
                  g_lastEvents = JSON.parse(eventsData);
                  g_lastEventId = lastEventId;

                  g_lastEvents.sort(function(a, b) {
                     return a.eventId - b.eventId;
                  });

                  console.log('Event ID ' + g_lastEventId);

                  sendEvents();
               } catch (e) {
                  console.log('Error parsing events file: ' + e.message);
                  return;
               }
            });
         }
      });
   }

   function pruneEventConnections() {
      var now = new Date().getTime();

      processEventConnections(function (connection) {
         if ((now - connection.time) >= TIMEOUT_MSEC) {
            return eventsResponse(g_lastEventId);
         } else {
            return null;
         }
      });
   }

   function go(req, res) {
      req.connection.setTimeout(0);
      res.header('Access-Control-Allow-Origin', '*');
      
      if (!isInt(req.query.lastEventId)) {
         res.send(errorResponse('ERR_ARGUMENT', 'Invalid argument: lastEventId'));
         return;
      }

      var lastEventId = parseInt(req.query.lastEventId);

      if (lastEventId < g_lastEventId) {
         // There are already newer events, no need to wait.
         res.send(eventsResponse(lastEventId));
         return;
      }

      // Hang on until there's an event to send.
      g_waitForEventConnections.push({
         res: res,
         lastEventId: lastEventId,
         time: new Date().getTime()
      });
   }

   pollEvents();
   fs.watchFile(EVENT_ID_FILE_PATH, { persist: true, interval: 500 }, function (a,b) { pollEvents(); });
   setInterval(pruneEventConnections, PRUNE_INTERVAL_MSEC);
   return go;
})());

/***/

app.post('/v2/notifications/waitForNotification', function(req, res) {
    // the intention is to poll periodically so the caller makes one "wait" call and we make a number of "poll"
    // calls. but it's hard to manage in the face of callers that might stop listening while we're still polling.
    // so just wait and then poll once. this effectively turns it into polling for the caller, but we don't have PHP
    // processes sitting around handling waitForNotification calls, and isn't that what life is all about?
    setTimeout(function() {
        req.connection.setTimeout(0);
        req.url = req.url.replace('waitForNotification', 'pollForNotification');
        proxy.web(req, res, {target: 'http://localhost:81'});
    }, NOTIFICATION_INTERVAL_MSEC);
});

/***/

proxy.on('error', function(err, req, res) {
   console.log('PROXY ERROR: ' + err.message);
   res.end();
});

proxy.on('proxyReq', function(proxyReq, req, res, options) {
   var encoding = req.acceptsEncodings('deflate', 'gzip');
   if (encoding !== false)
      proxyReq.setHeader('Accept-Encoding', encoding);
});

app.use('/', function(req, res) {
   req.connection.setTimeout(0);
   proxy.web(req, res, {target: 'http://localhost:81'});
});

app.timeout = 0;
app.listen(80);

// Set up HTTPS
var httpsOptions = {
   key: fs.readFileSync('/mnt/websites/_private/winchatty_ssl_certificate/winchatty.key'),
   cert: fs.readFileSync('/mnt/websites/_private/winchatty_ssl_certificate/winchatty_com.crt'),
   ca: [ 
      fs.readFileSync('/mnt/websites/_private/winchatty_ssl_certificate/winchatty_com.ca-bundle') 
   ],
   honorCipherOrder: true,
   ciphers: [
      "ECDHE-RSA-AES256-GCM-SHA384",
      "ECDH-RSA-AES256-GCM-SHA384",
      "DHE-RSA-AES256-GCM-SHA384",
      "ECDHE-RSA-AES256-GCM-SHA256",
      "ECDH-RSA-AES256-GCM-SHA256",
      "DHE-RSA-AES256-GCM-SHA256",
      "ECDHE-RSA-AES128-GCM-SHA256",
      "ECDH-RSA-AES128-GCM-SHA256",
      "DHE-RSA-AES128-GCM-SHA256",
      "ECDHE-RSA-AES256-SHA384",
      "ECDH-RSA-AES256-SHA384",
      "DHE-RSA-AES256-SHA384",
      "ECDHE-RSA-AES256-SHA256",
      "ECDH-RSA-AES256-SHA256",
      "DHE-RSA-AES256-SHA256",
      "ECDHE-RSA-AES128-SHA256",
      "ECDH-RSA-AES128-SHA256",
      "DHE-RSA-AES128-SHA256",
      "HIGH",
      "!aNULL",
      "!eNULL",
      "!EXPORT",
      "!DES",
      "!RC4",
      "!MD5",
      "!PSK",
      "!SRP",
      "!CAMELLIA"
   ].join(':')
};
var httpsServer = require('https').createServer(httpsOptions, app);
httpsServer.timeout = 0;
httpsServer.listen(443);
