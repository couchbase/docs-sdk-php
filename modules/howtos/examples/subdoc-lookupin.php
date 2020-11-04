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

// #tag::lookupInGet[]
$result = $collection->lookupIn("customer123", [
    new \Couchbase\LookupGetSpec("addresses.delivery.country")
]);
$country = $result->content(0);
printf("%s\n", $country);
// "United Kingdom"
// #end::lookupInGet[]

// #tag::lookupInExists[]
$result = $collection->lookupIn("customer123", [
    new \Couchbase\LookupExistsSpec("purchases.pending[-1]")
]);
printf("Path exists? %s\n", $result->exists(0) ? "true" : "false");
// Path exists? false
// #end::lookupInExists[]

// #tag::lookupInMulti[]
$result = $collection->lookupIn("customer123", [
    new \Couchbase\LookupGetSpec("addresses.delivery.country"),
    new \Couchbase\LookupExistsSpec("purchases.pending[-1]")
]);
printf("%s\n", $result->content(0));
printf("Path exists? %s\n", $result->exists(1) ? "true" : "false");
// United Kingdom
// Path exists? false
// #end::lookupInMulti[]