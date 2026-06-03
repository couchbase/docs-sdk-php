<?php

// tag::imports[]
// NOTE: Change the below vendor path to your own.
require_once '../../../vendor/autoload.php';

use Couchbase\ClusterOptions;
use Couchbase\Cluster;
// end::imports[]

// tag::connect[]
// Update these credentials for your Local instance!
$connectionString = "couchbase://localhost";
$options = new ClusterOptions();

$options->credentials("Administrator", "password");
$cluster = new Cluster($connectionString, $options);
// end::connect[]

// tag::bucket[]
// get a bucket reference
$bucket = $cluster->bucket("travel-sample");
// end::bucket[]

// tag::collection[]
// get a user-defined collection reference
$scope = $bucket->scope("tenant_agent_00");
$collection = $scope->collection("users");
// end::collection[]

// tag::upsert-get[]
$upsertResult = $collection->upsert("my-document-key", ["name" => "Ted", "Age" => 31]);

$getResult = $collection->get("my-document-key");

print_r($getResult->content());
// end::upsert-get[]

// tag::n1ql-query[]
$inventoryScope = $bucket->scope("inventory");
$queryResult = $inventoryScope->query("SELECT * FROM airline WHERE id = 10");

// Print result data to the terminal.
foreach ($queryResult->rows() as $row) {
    print_r($row);
}
// end::n1ql-query[]
