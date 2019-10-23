<?php
$connectionString = "couchbase://10.112.193.101";
$options = new \Couchbase\ClusterOptions();
$options->credentials("Administrator", "password");
$cluster = new \Couchbase\Cluster($connectionString, $options);

$bucket = $cluster->bucket("travel-sample");
$collection = $bucket->defaultCollection();

// #tag::positionalParams[]
$options = new \Couchbase\QueryOptions();
$options->positionalParameters(["hotel"]);
$result = $bucket->query('SELECT x.* FROM `travel-sample` x WHERE x.`type`=$1 LIMIT 10;', $options);
// #end::positionalParams[]

// #tag::namedParams[]
$options = new \Couchbase\QueryOptions();
$options->namedParameters(['type' => "hotel"]);
$result = $bucket->query('SELECT x.* FROM `travel-sample` x WHERE x.`type`=$type LIMIT 10;', $options);
// #end::namedParams[]

// #tag::results[]
$options = new \Couchbase\QueryOptions();
$options->positionalParameters(["hotel"]);
$result = $bucket->query('SELECT x.* FROM `travel-sample` x WHERE x.`type`=$1 LIMIT 10;', $options);

foreach($result->rows() as $row) {
    printf("Name: %s, Address: %s, Description: %s\n", $row["name"], $row["address"], $row["description"]);
}
// #end::results[]

// #tag::scan[]
// TODO: update when consistency is implemented
// #end::scan[]
