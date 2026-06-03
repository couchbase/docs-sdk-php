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

// #tag::mutateInIncrement[]
$result = $collection->mutateIn("customer123", [
    new \Couchbase\MutateCounterSpec("logins", 1)
]);

printf("%d\n", $result->content(0)); // 1
// #end::mutateInIncrement[]

// #tag::mutateInDecrement[]
$result = $collection->upsert("player432", ["gold" => 1000]);

$result = $collection->mutateIn("player432", [
    new \Couchbase\MutateCounterSpec("gold", -150)
]);
printf("player 432 now has %d gold remaining\n", $result->content(0));
// => player 432 now has 850 gold remaining
// #end::mutateInDecrement[]