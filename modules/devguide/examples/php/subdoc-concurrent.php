<?php
use \Couchbase\ClusterOptions;
use \Couchbase\Cluster;

$opts = new ClusterOptions();
$opts->credentials("Administrator", "password");
$cluster = new Cluster("couchbase://localhost", $opts);
$collection = $cluster->bucket("travel-sample")->scope("tenant_agent_00")->collection("users");

printf("Creating customer123 document\n");
$customer123 = json_decode(file_get_contents("customer123.json"));
$collection->upsert("customer123", $customer123);
printf("Created customer123 document\n");

// #tag::mutateInConcurrent[]
$operations = [new \Couchbase\MutateArrayAppendSpec("purchases.complete", [999]), new \Couchbase\MutateArrayAppendSpec("purchases.complete", [998])];
$pids = [];

for ($i = 0; $i < count($operations); $i++) {
    $pid = pcntl_fork();
    if ($pid == -1) {
        die("unable to spawn child process");
    } else if ($pid == 0) {
        // Child process
        concurrent_mutatein($operations[$i]);
        exit(0);
    } else {
        array_push($pids, $pid);
    }
}

foreach ($pids as $child) {
    pcntl_waitpid($child, $status);
}

function concurrent_mutatein($op) {
    $opts = new ClusterOptions();
    $opts->credentials("Administrator", "password");
    $cluster = new Cluster("couchbase://localhost", $opts);
    $collection = $cluster->bucket("travel-sample")->scope("tenant_agent_00")->collection("users");

    $result = $collection->mutateIn("customer123", [
        $op
    ]);
}
// #end::mutateInConcurrent[]
