<?php

use \Couchbase\ClusterOptions;
use \Couchbase\Cluster;
use \Couchbase\QueryOptions;
use \Couchbase\QueryScanConsistency;
use \Couchbase\MutationState;

$options = new ClusterOptions();
$options->credentials("Administrator", "password");
$cluster = new Cluster("couchbase://localhost", $options);

$bucket = $cluster->bucket("travel-sample");
$collection = $bucket->defaultCollection();

// tag::positionalParams[]
$options = new QueryOptions();
$options->positionalParameters(["hotel"]);
// NOTE: string is single-quoted to avoid PHP variable substitutions and pass '$1' as is
$result = $cluster->query('SELECT x.* FROM `travel-sample` x WHERE x.`type`=$1 LIMIT 10;', $options);
// end::positionalParams[]

// tag::namedParams[]
$options = new QueryOptions();
$options->namedParameters(['type' => "hotel"]);
$result = $cluster->query('SELECT x.* FROM `travel-sample` x WHERE x.`type`=$type LIMIT 10;', $options);
// end::namedParams[]

// tag::results[]
$options = new QueryOptions();
$options->positionalParameters(["hotel"]);
$result = $cluster->query('SELECT x.* FROM `travel-sample` x WHERE x.`type`=$1 LIMIT 10;', $options);

foreach ($result->rows() as $row) {
    printf("Name: %s, Address: %s, Description: %s\n", $row["name"], $row["address"], $row["description"]);
}
// end::results[]

// tag::scan[]
$query = 'SELECT x.* FROM `travel-sample` x WHERE x.`type`="hotel" LIMIT 10';
$opts = new QueryOptions();
$opts->scanConsistency(QueryScanConsistency::REQUEST_PLUS);
$res = $cluster->query($query, $opts);
// end::scan[]
$idx = 1;
foreach ($res->rows() as $row) {
    printf("%d. %s, \"%s\"\n", $idx++, $row['country'], $row['name']);
}
printf("Execution Time: %d\n", $res->metaData()->metrics()['executionTime']);

// tag::consistentWith[]
// create/update document (mutation)
$res = $collection->upsert("id", ["name" => "somehotel", "type" => "hotel"]);

// create mutation state from mutation results
$state = new MutationState();
$state->add($res);

// use mutation state with query optionss
$opts = new QueryOptions();
$opts->consistentWith($state);
$res = $cluster->query('SELECT x.* FROM `travel-sample` x WHERE x.`type`="hotel" AND x.name LIKE "%hotel%" LIMIT 10', $opts);
// end::consistentWith[]
$idx = 1;
foreach ($res->rows() as $row) {
    printf("%d. %s\n", $idx++, $row['name']);
}

printf("Execution Time: %d\n", $res->metaData()->metrics()['executionTime']);

// tag::scope-level-query[]
$opts = new QueryOptions();
$opts->namedParameters(['country' => "France"]);

$scope = $bucket->scope("inventory");
$result = $scope->query('SELECT x.* FROM `airline` x WHERE x.`country`=$country LIMIT 10;', $opts);

foreach ($result->rows() as $row) {
    printf("Name: %s, Callsign: %s, Country: %s\n", $row["name"], $row["callsign"], $row["country"]);
}
// end::scope-level-query[]
