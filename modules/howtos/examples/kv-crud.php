<?php

use \Couchbase\ClusterOptions;
use \Couchbase\Cluster;
use \Couchbase\InsertOptions;
use \Couchbase\ReplaceOptions;
use \Couchbase\UpsertOptions;
use \Couchbase\GetOptions;
use \Couchbase\RemoveOptions;
use \Couchbase\DurabilityLevel;

$opts = new ClusterOptions();
$opts->credentials("Administrator", "password");
$cluster = new Cluster("couchbase://localhost", $opts);
$bucket = $cluster->bucket("travel-sample");
$collection = $bucket->defaultCollection();

// tag::insert[]
$document = ["foo" => "bar", "bar" => "foo"];
$res = $collection->insert("document-key-new", $document);
printf("document \"document-key-new\" has been created with CAS \"%s\"\n", $res->cas());
// end::insert[]

// Insert document with options
// tag::insertwithoptions[]
$document = ["foo" => "bar", "bar" => "foo"];
$opts = new InsertOptions();
$opts->timeout(300000 /* milliseconds */);
$res = $collection->insert("document-key", $document, $opts);
printf("document \"document-key\" has been created with CAS \"%s\"\n", $res->cas());
// end::insertwithoptions[]

// tag::replacewithcas[]
// Replace document with incorrect CAS
$opts = new ReplaceOptions();
$opts->timeout(300000 /* milliseconds */);
$invalidCas = "776t3gAAAAA=";
$opts->cas($invalidCas);
try {
    $collection->replace("document-key", $document, $opts);
} catch (\Couchbase\Exception\CasMismatchException $ex) {
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
// end::replacewithcas[]

// tag::removewithoptions[]
$opts = new RemoveOptions();
$opts->timeout(5000); // 5 seconds
$result = $collection->remove("document-key", $opts);
printf("document \"document-key\" \"%s\" been removed successfully.\n", $res->cas());
// end::removewithoptions[]

// tag::upsertwithexpiry[]
$document = ["foo" => "bar", "bar" => "foo"];
$opts = new UpsertOptions();
$opts->expiry(60 * 1000 /* 60 seconds */);
$res = $collection->upsert("document-key", $document, $opts);
printf("document \"document-key\" has been created with CAS \"%s\"\n", $res->cas());
// end::upsertwithexpiry[]

// Get
// tag::get[]
$res = $collection->get("document-key");
$doc = $res->content();
printf("document \"document-key\" has content: \"%s\" CAS \"%s\"\n", json_encode($doc), $res->cas());
// end::get[]

// tag::getwithoptions[]
$opts = new GetOptions();
$opts->timeout(3000 /* milliseconds */);
$res = $collection->get("document-key", $opts);
$doc = $res->content();
printf("document \"document-key\" has content: \"%s\" CAS \"%s\"\n", json_encode($doc), $res->cas());
// end::getwithoptions[]

// tag::upsertwithdurability[]
// Upsert with Durability
$opts = new UpsertOptions();
$opts->timeout(3000 /* milliseconds */);
$opts->durabilityLevel(DurabilityLevel::MAJORITY);
$res = $collection->upsert("document-key2", $opts);
printf("document \"document-key2\" has been created with CAS \"%s\"\n", $res->cas());
// end::upsertwithdurability[]

// tag::namedcollectionupsert[]
$document = ["name" => "John Doe", "preferred_email" => "johndoe111@test123.test"];
$opts = new UpsertOptions();

$agentScope = $bucket->scope("tenant_agent_00");
$usersCollection = $agentScope->collection("users");

$res = $usersCollection->upsert("user-key", $document, $opts);
printf("document \"user-key\" has been created with CAS \"%s\"\n", $res->cas());
// end::namedcollectionupsert[]

// Cleanup example data to avoid errors when running locally (mainly for inserts).
$opts = new RemoveOptions();
$collection->remove("document-key-new", $opts);
$collection->remove("document-key", $opts);
