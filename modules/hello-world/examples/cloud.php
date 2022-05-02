<?php

// tag::imports[]
require_once 'Couchbase/autoload.php';

use \Couchbase\ClusterOptions;
use \Couchbase\Cluster;
// end::imports[]

// tag::connect[]
// Update these credentials for your Capella instance!
$connectionString = "couchbases://cb.njg8j7mwqnvwjqah.cloud.couchbase.com";
$options = new \Couchbase\ClusterOptions();

$options->credentials("username", "Password!123");
$options->keyValueTimeout(10 * 1000);
$cluster = new \Couchbase\Cluster($connectionString, $options);
// end::connect[]

// tag::bucket[]
// get a bucket reference
$bucket = $cluster->bucket("travel-sample");
// end::bucket[]

// tag::collection[]
// get a user-defined collection reference$scope = $bucket->scope("tenant_agent_00");
$scope = $bucket->scope("tenant_agent_00");
$collection = $scope->collection("users");
// end::collection[]

// tag::upsert-get[]
$upsertResult = $collection->upsert("my-document-key", ["name" => "Ted", "Age" => 31]);

$getResult = $collection->get("my-document-key");

print_r($getResult->content());
// end::upsert-get[]

// tag::n1ql-query[]
$queryResult = $cluster->query("select \"Hello World\" as greeting");

// Iterate over the rows to access result data and print to the terminal.
foreach ($queryResult->rows() as $row) {
    printf("%s\n", $row["greeting"]);
}
// end::n1ql-query[]
