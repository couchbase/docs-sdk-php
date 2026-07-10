<?php

use Couchbase\QueryOptions;
use Couchbase\ClusterOptions;
use Couchbase\Cluster;
use Couchbase\CreateQueryPrimaryIndexOptions;
use Couchbase\CreateQueryIndexOptions;
use \Couchbase\QueryScanConsistency;

$connectionString = 'localhost';
$clusterOpts = new ClusterOptions();
$clusterOpts->credentials('Administrator', 'password');

$cluster = new Cluster($connectionString, $clusterOpts);
$bucket = $cluster->bucket('travel-sample');
$collection = $bucket->scope('inventory')->collection('airline');

print("Example - [prepared-statement]\n");
// tag::prepared-statement[]
$query = "SELECT count(*) FROM `travel-sample`.inventory.airport where country = $1";
$opts = new QueryOptions();
$opts->adhoc(false);
$opts->positionalParameters(['France']);

$result = $cluster->query($query, $opts);
foreach ($result->rows() as $row) {
    // do something
}
// end::prepared-statement[]

print("Example - [create-index]\n");
// tag::create-index[]
$mgr = $cluster->queryIndexes();

$mgr->createPrimaryIndex('travel-sample');
$mgr->createIndex('travel-sample', 'ix_name', ['name']);
$mgr->createIndex('travel-sample', 'ix_email', ['email']);
// end::create-index[]

dropIndexes($mgr);

print("Example - [deferred-index]\n");
// tag::deferred-index[]
$indexOpts = new CreateQueryIndexOptions();
$primaryIndexOpts = new CreateQueryPrimaryIndexOptions();

$mgr->createPrimaryIndex('travel-sample', $primaryIndexOpts->deferred(true));
$mgr->createIndex('travel-sample', 'ix_name', ['name'], $indexOpts->deferred(true));
$mgr->createIndex('travel-sample', 'ix_email', ['email'], $indexOpts->deferred(true));

$indexesToBuild = $mgr->buildDeferredIndexes('travel-sample');
$mgr->watchIndexes('travel-sample', $indexesToBuild, 2);
// end::deferred-index[]

dropIndexes($mgr);

print("Example - [index-consistency]\n");
// tag::index-consistency[]
$random = rand(0, 10000000);
$userDoc = [
    'name' => 'Brass Doorknob',
    'email' => 'brass.doorknob@juno.com',
    'random' => $random
];

$collection->upsert(sprintf("user:%d", $random), $userDoc);
$queryOpts = new QueryOptions();
$cluster->query(
    "SELECT name, email, random, META().id FROM `travel-sample`.inventory.airport WHERE $1 IN name",
    $queryOpts->positionalParameters(['Brass'])
);
// end::index-consistency[]

print("Example - [index-consistency-request-plus]\n");
// tag::index-consistency-request-plus[]
$queryOpts->scanConsistency(QueryScanConsistency::REQUEST_PLUS);
$queryOpts->positionalParameters(['Brass']);
$cluster->query(
    "SELECT name, email, random, META().id FROM `travel-sample`.inventory.airport WHERE $1 IN name",
    $queryOpts,
);
// end::index-consistency-request-plus[]

function dropIndexes($mgr) {
    print("Dropping indexes...\n");
    $mgr->dropPrimaryIndex('travel-sample');
    $mgr->dropIndex('travel-sample', 'ix_name');
    $mgr->dropIndex('travel-sample', 'ix_email');
}