<?php
use \Couchbase\ClusterOptions;
use \Couchbase\Cluster;
use \Couchbase\InsertOptions;
use \Couchbase\ReplaceOptions;
use \Couchbase\GetOptions;
use \Couchbase\RemoveOptions;
use \Couchbase\DurabilityLevel;

$opts = new ClusterOptions();
$opts->credentials("Administrator", "password");
$cluster = new Cluster("couchbase://192.168.1.101", $opts);

$collection = $cluster->bucket("travel-sample")->defaultCollection();

// tag:insert[]
$document = ["foo" => "bar", "bar" => "foo"];
$res = $collection->insert("document-key-new", $document);
printf("document \"document-key\" has been created with CAS \"%s\"\n", $res->cas());
// end:insert[]

// Insert document with options
// tag:insertwithoptions[]
$document = ["foo" => "bar", "bar" => "foo"];
$opts = new InsertOptions();
$opts->timeout(300000 /* microseconds */);
$res = $collection->insert("document-key", $document, $opts);
printf("document \"document-key\" has been created with CAS \"%s\"\n", $res->cas());
// end:insertwithoptions[]

// tag:replacewithcas[]
// Replace document with incorrect CAS
$opts = new ReplaceOptions();
$opts->timeout(300000 /* microseconds */);
$invalidCas = "776t3gAAAAA=";
$opts->cas($invalidCas);
try {
    $collection->replace("document-key", $document, $opts);
} catch (\Couchbase\CasMismatchException $ex) {
    printf("document \"document-key\" cannot be replaced with CAS \"%s\"\n", $invalidCas);
}

// Get and Replace document with CAS
$res = $collection->get("document-key");
$doc = $res->content();
$doc["bar"] = "moo";

$opts = new ReplaceOptions();
$oldCas = $res->cas();
$opts->cas($oldCas);
$res = $collection->replace("document-key", $doc, $opts);
printf("document \"document-key\" \"%s\" been replaced successfully. New CAS \"%s\"\n", $oldCas, $res->cas());
// end:replacewithcas[]

// tag:removewithoptions[]
$opts = new RemoveOptions();
$options->timeout(5000000); // 5 seconds
$result = $collection->remove("document-key");
// tag:removewithoptions[]

// tag:upsertwithexpiry[]
$document = ["foo" => "bar", "bar" => "foo"];
$opts = new UpsertOptions();
$opts->expiry(60 /* seconds */);
$res = $collection->upsert("document-key", $document, $opts);
printf("document \"document-key\" has been created with CAS \"%s\"\n", $res->cas());
// end:upsertwithexpiry[]

// Get
// tag:get[]
$res = $collection->get("document-key");
$doc = $res->content();
printf("document \"document-key\" has content: \"%s\" CAS \"%s\"\n", json_encode($doc), $res->cas());
// end:get[]

// tag:getwithoptions[]
$opts = new GetOptions();
$opts->timeout(300000 /* microseconds */);
$res = $collection->get("document-key", $opts);
$doc = $res->content();
printf("document \"document-key\" has content: \"%s\" CAS \"%s\"\n", json_encode($doc), $res->cas());
// tag:getwithoptions[]

// tag:removewithdurability[]
// Remove with Durability
$opts = new RemoveOptions();
$opts->timeout(3000000 /* microseconds */);
$opts->durabilityLevel(DurabilityLevel::MAJORITY);
$res = $collection->remove("document-key", $opts);
printf("document \"document-key\" has been removed\n");
// end:removewithdurability[]
