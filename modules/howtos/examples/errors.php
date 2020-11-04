<?php
use \Couchbase\ClusterOptions;
use \Couchbase\Cluster;

$opts = new ClusterOptions();
$opts->credentials("Administrator", "password");
$cluster = new Cluster("couchbase://localhost", $opts);

$collection = $cluster->bucket("travel-sample")->defaultCollection();

// #tag::catch[]
try {
    $collection->get("foo");
} catch (\Couchbase\KeyNotFoundException $ex) {
    printf("Document does not exist, creating");
    $collection->upsert("foo", ["bar" => 42]);
}
// #end::catch[]