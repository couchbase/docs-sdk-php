<?php
ini_set("couchbase.log_level", "ERROR");

// #tag::loading[]
$concurrency = 4; // number of processes
$sample_name = "beer-sample";
$sample_zipball = "/opt/couchbase/samples/$sample_name.zip";
printf("Using '%s' as input\n", $sample_zipball);
system("rm -rf /tmp/$sample_name");
system("unzip -q -d /tmp $sample_zipball");
$files = glob("/tmp/$sample_name/docs/*.json");
$batches = [];
for ($i = 0; $i < $concurrency; $i++) {
  $batches[$i] = [];
}
printf("Bundle '%s' contains %d files\n", $sample_name, count($files));
for ($i = 0; $i < count($files); $i++) {
  array_push($batches[$i % $concurrency], $files[$i]);
}
#end::loading[]

// #tag::batching[]
$children = [];
for ($i = 0; $i < $concurrency; $i++) {
  $pid = pcntl_fork();
  if ($pid == -1) {
    die("unable to spawn child process");
  } else if ($pid == 0) {
    printf("Start a process to upload a batch of %d files\n", count($batches[$i]));
    upload_batch($i, $batches[$i]);
    exit(0);
  } else {
    array_push($children, $pid);
  }
}

foreach ($children as $child) {
  pcntl_waitpid($child, $status);
}

use \Couchbase\Cluster;
use \Couchbase\ClusterOptions;
function upload_batch($id, $batch) {
  $options = new ClusterOptions();
  $options->credentials("Administrator", "password");
  $cluster = new Cluster("couchbase://10.112.193.101", $options);
  $collection = $cluster->bucket("default")->defaultCollection();
  foreach ($batch as $path) {
    $collection->upsert($path, json_decode(file_get_contents($path)));
  }
}
// #end::batching[]
