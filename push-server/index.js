// WinChatty Server
// Copyright (C) 2014 Brian Luft
// 
// This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public 
// License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later 
// version.
// 
// This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied 
// warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more 
// details.
// 
// You should have received a copy of the GNU General Public License along with this program; if not, write to the Free 
// Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

var express = require('express');
var fs = require('fs');
var app = express();
var httpProxy = require('http-proxy');
var proxy = httpProxy.createServer({ timeout: 660000 });

app.use(require('morgan')('combined'));

var TIMEOUT_MSEC = 10 * 60 * 1000;
var PRUNE_INTERVAL_MSEC = 1000;
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

proxy.on('error', function(err, req, res) {
   console.log('PROXY ERROR: ' + err.message);
   res.end();
});

app.use('/frontend/', function(req, res) {
   proxy.web(req, res, {target: 'http://localhost:3000'});
});

app.use('/', function(req, res) {
   req.connection.setTimeout(0);
   proxy.web(req, res, {target: 'http://localhost:81'});
});

app.timeout = 0;
app.listen(80);

// Set up HTTPS, but only if the key files are present.
var winchattyKeyPath = '/mnt/websites/_private/winchatty_ssl_certificate/winchatty.key';
var winchattyCertPath = '/mnt/websites/_private/winchatty_ssl_certificate/winchatty_com.crt';
var caCertPath = '/mnt/websites/_private/winchatty_ssl_certificate/PositiveSSLCA2.crt';
if (fs.existsSync(winchattyKeyPath) && fs.existsSync(winchattyCertPath)) {
   var httpsOptions = {
      key: fs.readFileSync(winchattyKeyPath),
      cert: fs.readFileSync(winchattyCertPath)
   };
   if (fs.existsSync(caCertPath)) {
      httpsOptions.ca = [ fs.readFileSync(caCertPath) ];
   }
   var httpsServer = require('https').createServer(httpsOptions, app);
   httpsServer.timeout = 0;
   httpsServer.listen(443);
}
