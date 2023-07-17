<?php

use \Couchbase\ClusterOptions;
use \Couchbase\Cluster;

$opts = new ClusterOptions();
$opts->credentials("Administrator", "password");
$cluster = new Cluster("couchbase://localhost", $opts);

$collection = $cluster->bucket("travel-sample")->scope("inventory")->collection("airport");


// tag::document-not-found-exception[]
try {
    $collection->get("foo");
} catch (\Couchbase\Exception\DocumentNotFoundException $ex) {
    printf("Document does not exist, creating. \n");
    $collection->upsert("foo", ["bar" => 42]);
}
// end::document-not-found-exception[]


// tag::key-exists-exception[]
try {
    $collection->insert("foo", ["bar" => 43]);
} catch (\Couchbase\KeyExistsException $ex) {
    printf("Document already exists. \n");
}
// end::key-exists-exception[]

$max_size = 1024 * 1024 * 20; # 20MB
$big_object = str_repeat(' ', $max_size + 1);

// tag::value-too-big-exception[]
try {
    $collection->insert("big", $big_object);
} catch (\Couchbase\ValueTooBigException $ex) {
    printf("Document is bigger than maximum size (20MB). \n");
}
// end::value-too-big-exception[]


// tag::cas-mismatch-exception[]
$result1 = $collection->get("foo");
$original_cas = $result1->cas();

$opts = new \Couchbase\ReplaceOptions();

$result2 = $collection->replace("foo",
                                ["bar" => 44],
                                $opts->cas($original_cas));
$updated_cas = $result2->cas();

try {
    $collection->replace("foo",
                         ["bar" => 45],
                         $opts->cas($original_cas));
                         # oops, we should have used $updated_cas!
} catch (\Couchbase\CasMismatchException $ex) {
    printf("CAS mismatch error. \n");
}
// end::cas-mismatch-exception[]


// tag::retry[]
$max_attempts = 5;
for ($attempt = 1; $attempt <= $max_attempts; $attempt++) {
    printf("Attempt $attempt. \n");
    try {
        $result = $collection->get("expected-document");
        break;
    }
    catch (\Couchbase\Exception\DocumentNotFoundException $ex) {
        printf("Document still not created. \n");
        usleep(100);
        continue;
    }
    catch (\Couchbase\NetworkException $ex) {
      printf("An unrecoverable network exception happened! \n");
      break;
    }
}
// end::retry[]
