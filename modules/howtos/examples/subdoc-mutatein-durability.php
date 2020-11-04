<?php
use \Couchbase\ClusterOptions;
use \Couchbase\Cluster;

$opts = new ClusterOptions();
$opts->credentials("Administrator", "password");
$cluster = new Cluster("couchbase://localhost", $opts);

$collection = $cluster->bucket("default")->defaultCollection();

printf("Creating customer123 document\n");
$customer123 = json_decode(file_get_contents("customer123.json"));
$collection->upsert("customer123", $customer123);
printf("Created customer123 document\n");

// #tag::mutateInDurableWrites[]
$options = new \Couchbase\MutateInOptions();
$options->durabilityLevel(\Couchbase\DurabilityLevel::MAJORITY);
$result = $collection->mutateIn("customer123", [
    new \Couchbase\MutateUpsertSpec("name", "dave")
], $options);
// #end::mutateInDurableWrites[]
