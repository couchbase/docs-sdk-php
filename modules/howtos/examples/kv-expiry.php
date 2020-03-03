<?php
use \Couchbase\ClusterOptions;
use \Couchbase\Cluster;
use \Couchbase\UpsertOptions;
use \Couchbase\TouchOptions;

$opts = new ClusterOptions();
$opts->credentials("Administrator", "password");
$cluster = new Cluster("couchbase://192.168.1.101", $opts);

$collection = $cluster->bucket("travel-sample")->defaultCollection();

$document = [
    "foo" => "bar",
    "bar" => "foo",
];

$key = "document-key";

// Upsert with Expiry
$opts = new UpsertOptions();
$opts->expiry(60 /* seconds */);
$res = $collection->upsert($key, $document, $opts);

// Retrieve the document immediately, must be exist
$res = $collection->get($key);
printf("[get] document content: %s\n", var_export($res->content(), true));

// Touch the document to adjust expiration time
// #tag:touch[]
$collection->touch($key, 60 /* seconds */);
// #end:touch[]

// Touch the document to adjust expiration time
// #tag:touchwithoptions[]
$opts = new TouchOptions();
$opts->timeout(500000 /* microseconds */);
$collection->touch($key, 60 /* seconds */);
// #end:touchwithoptions[]

// Get and touch retrieves the document and adjusting expiration time
// #tag:getandtouch[]
$res = $collection->getAndTouch($key, 1 /* seconds */);
printf("[getAndTouch] document content: %s\n", var_export($res->content(), true));

sleep(2); // wait until the document will expire

try {
    $collection->get($key);
} catch (Couchbase\DocumentNotFoundException $ex) {
    printf("The document does not exist\n");
}
// #end:getandtouch[]

// Output:
//
//     [get] document content: array (
//       'foo' => 'bar',
//       'bar' => 'foo',
//     )
//     [getAndTouch] document content: array (
//       'foo' => 'bar',
//       'bar' => 'foo',
//     )
//     The document does not exist
