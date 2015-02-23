<?
# must run as root.  run with the working directory set to the directory containing this file.
# this will shut down postgresql and pgbouncer.
# you must have already run "linode configure".

$accesskey = readline('S3 access key: ');
$secretkey = readline('S3 secret key: ');
$bucket = readline('S3 bucket: ');
$pg_backup_filename = readline('Filename of pgsql fs-level backup in the S3 bucket: ');
   #todo: create this in this script automatically

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

$stackscript_id = 11509; # brianluft/winchatty-index-node
$distribution_id = 124; # Ubuntu 14.04 LTS
$kernel_id = 138; # Latest 64 bit (3.18.5-x86_64-linode52)
$datacenter_id = 6; # Newark, NJ, USA

$ranges = array(
   array(    1,  9999),
   array(10000, 19999),
   array(20000, 29999),
   array(30000, 39999),
   array(40000, 49999),
);

$linodes = array(); # array of array('id' => <linode_id>, 'start_id' => 1, 'end_id' => 1000, 'status' => 1), ...

foreach ($ranges as $range) {
   $start_id = $range[0];
   $end_id = $range[1];

   echo "Creating linode for range $start_id to $end_id...\n";

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
      'Label' => 'wc_idx_' . $start_id . '_' . $linode_id,
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
      'StackScriptUDFResponses' => json_encode(array(
         'STARTID' => intval($start_id),
         'ENDID' => intval($end_id),
         'ACCESSKEY' => strval($accesskey),
         'SECRETKEY' => strval($secretkey),
         'BUCKET' => strval($bucket),
         'PGSQLBACKUPNAME' => strval($pg_backup_filename))),
      'Label' => 'disk',
      'Size' => 24000,
      'rootPass' => 'super_hard_password'));

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

   # wait for this instance to boot up
   $booted = false;
   while (!$booted) {
      sleep(5);
      $list_result = linode_api('linode.list', array());
      foreach ($list_result['DATA'] as $vm)
         if ($vm['LINODEID'] == $linode_id)
            $booted = ($vm['STATUS'] == 1);
   }

   $linodes[$linode_id] = array('id' => $linode_id, 'start_id' => $range[0], 'end_id' => $range[1],
      'disk_id' => $disk_id, 'swap_id' => $swap_id);
}

echo "Linode IDs: ";
foreach ($linodes as $linode) {
   echo $linode['id'] . ' ';
}
echo "\n";

# wait for the instances to shut themselves down
$start_time = time();
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

# delete the instances
foreach ($linodes as $linode) {
   echo "Deleting linode " . $linode['id'] . "...\n";

   linode_api('linode.disk.delete', array(
      'LinodeID' => $linode['id'],
      'DiskID' => $linode['disk_id']));

   linode_api('linode.disk.delete', array(
      'LinodeID' => $linode['id'],
      'DiskID' => $linode['swap_id']));

   # wait for the disks to be deleted
   while (true) {
      $list_result = linode_api('linode.disk.list', array('LinodeID' => $linode['id']));
      if (empty($list_result['DATA']))
         break;
      sleep(5);
   }

   linode_api('linode.delete', array(
      'LinodeID' => $linode['id']));
}

# download the files they uploaded to the s3 bucket
mkdir('/tmp/winchatty-cloud-index/');
foreach ($ranges as $range) {
   $min_id = $range[0];
   $max_id = $range[1];

   echo "Downloading results $min_id ... $max_id\n";

   system('s3cmd get ' .
      's3://' . $bucket . '/dts-index-chunk-' . $min_id . '.tar.gz ' .
      '/tmp/winchatty-cloud-index/dts-index-chunk-' . $min_id . '.tar.gz');

   system('s3cmd del ' .
      's3://' . $bucket . '/dts-index-chunk-' . $min_id . '.tar.gz ');
}

echo "Done.\n";
