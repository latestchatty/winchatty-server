<?
# must run as root.  run with the working directory set to the directory containing this file.
# this will shut down postgresql and pgbouncer.
# you must have already run "linode configure".

$accesskey = readline('S3 access key: ');
$secretkey = readline('S3 secret key: ');
$bucket = readline('S3 bucket: ');
$pg_backup_filename = readline('Filename of pgsql fs-level backup in the S3 bucket: ');
   #todo: create this in this script automatically

$start_time = time();

if (!file_exists('/root/.linodecli/config'))
   die("/root/.linodecli/config does not exist; you must run 'linode configure' first.\n");

$bucket = trim($bucket);
$pg_backup_filename = trim($pg_backup_filename);
$api_key = str_replace('api-key ', '', file_get_contents('/root/.linodecli/config'));

file_put_contents('/root/.s3cfg', 
   str_replace('SECRETKEY', $secretkey, 
      str_replace('ACCESSKEY', $accesskey, file_get_contents('s3cmd/s3cfg'))));

$accesskey = str_replace('/', "\\/", trim($accesskey));
$secretkey = str_replace('/', "\\/", trim($secretkey));

function linode_api($method, $args) {
   global $api_key;
   $args['api_key'] = $api_key;
   $args['api_action'] = $method;
   $url = 'https://api.linode.com/?' . http_build_query($args);
   $result_json = file_get_contents($url);
   $result = json_decode($result_json, true);
   if ($result === false)
      die("Unexpected result from $method: $result_json\n");
   else if (!empty($result['ERRORARRAY']))
      die("Error result from $method: $result_json\n");
   else
      return $result;
}

function create_linode($label, $stackscript_id, $stackscript_args) {
   global $datacenter_id;
   global $distribution_id;

   # create the virtual machine
   $create_result = linode_api('linode.create', array(
      'DatacenterID' => $datacenter_id,
      'PlanID' => 1));
   if ($create_result === false)
      die('Unexpected result from linode.create.');
   $linode_id = intval($create_result['DATA']['LinodeID']);
   if ($linode_id <= 0)
      die('Unexpected result from linode.create.');

   linode_api('linode.update', array(
      'LinodeID' => $linode_id,
      'Label' => $label . '_li' . $linode_id,
      'lpm_displayGroup' => 'wc_idx',
      'Alert_cpu_enabled' => 0,
      'Alert_diskio_enabled' => 0,
      'Alert_bwin_enabled' => 0,
      'Alert_bwout_enabled' => 0,
      'watchdog' => 0));

   $disk_result = linode_api('linode.disk.createfromstackscript', array(
      'LinodeID' => $linode_id,
      'DistributionID' => $distribution_id,
      'StackScriptID' => $stackscript_id,
      'StackScriptUDFResponses' => json_encode($stackscript_args),
      'Label' => 'disk',
      'Size' => 24000,
      'rootPass' => uniqid()));

   $disk_id = $disk_result['DATA']['DiskID'];

   $swap_result = linode_api('linode.disk.create', array(
      'LinodeID' => $linode_id,
      'Label' => 'swap',
      'Type' => 'swap',
      'Size' => 256));

   $swap_id = $swap_result['DATA']['DiskID'];

   $config_result = linode_api('linode.config.create', array(
      'LinodeID' => $linode_id,
      'KernelID' => $kernel_id,
      'Label' => 'config',
      'DiskList' => $disk_id . ',' . $swap_id));

   linode_api('linode.boot', array(
      'LinodeID' => $linode_id));

   return array('id' => $linode_id, 'start_id' => $range[0], 'end_id' => $range[1],
      'disk_id' => $disk_id, 'swap_id' => $swap_id);
}

function wait_until_booted($linode_id) {
   # wait for this instance to boot up
   $booted = false;
   while (!$booted) {
      $list_result = linode_api('linode.list', array());
      foreach ($list_result['DATA'] as $vm)
         if ($vm['LINODEID'] == $linode_id)
            $booted = ($vm['STATUS'] == 1);
      if (!$booted)
         sleep(5);
   }
}

$index_stackscript_id = 11509; # brianluft/winchatty-index-node
$filehost_stackscript_id = 11514; # brianluft/winchatty-filehost
$distribution_id = 124; # Ubuntu 14.04 LTS
$kernel_id = 138; # Latest 64 bit (3.18.5-x86_64-linode52)
$datacenter_id = 6; # Newark, NJ, USA

$ranges = array(
   array(    0,  9999)/*,
   array(10000, 19999),
   array(20000, 29999),
   array(30000, 39999),
   array(40000, 49999),
   array(50000, 59999),
   array(60000, 69999),
   array(70000, 79999),
   array(80000, 89999),
   array(90000, 99999),*/
);

$http_pass = uniqid();

echo "Creating linode to host the database dump...\n";
$filehost_linode = create_linode('wc_idx_filehost', $filehost_stackscript_id, array(
   'ACCESSKEY' => strval($accesskey),
   'SECRETKEY' => strval($secretkey),
   'BUCKET' => strval($bucket),
   'PGSQLBACKUPNAME' => strval($pg_backup_filename),
   'HTTPPASS' => $http_pass));
wait_until_booted($filehost_linode['id']);
echo "File host has booted.\n";

# filehost will put "winchatty_filehost_url" in the bucket when it's ready to serve the file
if (file_exists('/tmp/winchatty_filehost_url'))
   unlink('/tmp/winchatty_filehost_url');
while (!file_exists('/tmp/winchatty_filehost_url')) {
   system("s3cmd get s3://$bucket/winchatty_filehost_url /tmp/winchatty_filehost_url >/dev/null 2>/dev/null");
   sleep(5);
}
$filehost_url = file_get_contents('/tmp/winchatty_filehost_url');

$linodes = array(); # array of array('id' => <linode_id>, 'start_id' => 1, 'end_id' => 1000, 'status' => 1), ...

foreach ($ranges as $range) {
   $start_id = $range[0];
   $end_id = $range[1];

   echo "Creating linode for range $start_id to $end_id...\n";

   $linode = create_linode('wc_idx_' . $start_id, $index_stackscript_id, array(
      'STARTID' => intval($start_id),
      'ENDID' => intval($end_id),
      'ACCESSKEY' => strval($accesskey),
      'SECRETKEY' => strval($secretkey),
      'BUCKET' => strval($bucket),
      'PGSQLBACKUPURL' => strval($filehost_url),
      'PGSQLBACKUPPASS' => strval($http_pass)));

   $linodes[$linode_id] = array('id' => $linode_id, 'start_id' => $range[0], 'end_id' => $range[1],
      'disk_id' => $disk_id, 'swap_id' => $swap_id);
}

foreach ($linodes as $linode) {
   wait_until_booted($linode['id']);
   echo "Linode " . $linode['id'] . " has booted.\n";
}

echo "Linode IDs: ";
foreach ($linodes as $linode) {
   echo $linode['id'] . ' ';
}
echo "\n";

# wait for the instances to shut themselves down
while (true) {
   $list_result = linode_api('linode.list', array());
   $num_total = 0;
   $num_running = 0;
   foreach ($list_result['DATA'] as $vm) {
      if (isset($linodes[intval($vm['LINODEID'])])) {
         $num_total++;
         if ($vm['STATUS'] == 1)
            $num_running++;
      }
   }

   $time_so_far = floor((time() - $start_time) / 60);
   echo "Waiting for $num_running of $num_total instances to finish... $time_so_far minute(s)      \r";

   if ($num_running == 0)
      break;

   sleep(30);
}

echo "\n";

echo "Sending command to delete all disks...\n";

# delete the instances
foreach ($linodes as $linode) {
   linode_api('linode.disk.delete', array(
      'LinodeID' => $linode['id'],
      'DiskID' => $linode['disk_id']));

   linode_api('linode.disk.delete', array(
      'LinodeID' => $linode['id'],
      'DiskID' => $linode['swap_id']));
}

echo "Waiting for disks to finish deleting...\n";

foreach ($linodes as $linode) {
   # wait for the disks to be deleted
   while (true) {
      $list_result = linode_api('linode.disk.list', array('LinodeID' => $linode['id']));
      if (empty($list_result['DATA']))
         break;
      sleep(5);
   }

   linode_api('linode.delete', array(
      'LinodeID' => $linode['id']));

   echo "Deleted linode " . $linode['id'] . "\n";
}

echo "Downloading results...\n";

# download the files they uploaded to the s3 bucket
mkdir('/tmp/winchatty-cloud-index/');
foreach ($ranges as $range) {
   $min_id = $range[0];
   $max_id = $range[1];

   system('s3cmd get ' .
      's3://' . $bucket . '/dts-index-chunk-' . $min_id . '.tar.gz ' .
      '/tmp/winchatty-cloud-index/dts-index-chunk-' . $min_id . '.tar.gz');

   system('s3cmd del ' .
      's3://' . $bucket . '/dts-index-chunk-' . $min_id . '.tar.gz ');
}

echo "Done.\n";
