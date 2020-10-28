<?php
use \Couchbase\ClusterOptions;
use \Couchbase\Cluster;

$opts = new ClusterOptions();
$opts->credentials("Administrator", "password");
$cluster = new Cluster("couchbase://localhost", $opts);

$collection = $cluster->bucket("default")->defaultCollection();

printf("Creating customer123 document\n");
$customer123 = json_decode(file_get_contents("customer123.json"));
$collection->upsert("customer123", $customer123);
printf("Created customer123 document\n");


// #tag::mutateInArrayAppend[]
$result = $collection->MutateIn("customer123", [
    new \Couchbase\MutateArrayAppendSpec("purchases.complete", [777])
]);
// purchases.complete is now [339, 976, 442, 666, 777]
// #end::mutateInArrayAppend[]

// #tag::mutateInArrayPrepend[]
$result = $collection->MutateIn("customer123", [
    new \Couchbase\MutateArrayPrependspec("purchases.abandoned", [18])
]);
// purchases.abandoned is now [18, 157, 49, 999]
// #end::mutateInArrayPrepend[]

// #tag::mutateInArrayDoc[]
$result = $collection->upsert("my_array", []);
$result = $collection->mutateIn("my_array", [
    new \Couchbase\MutateArrayAppendSpec("", ["some element"])
]);
// the document my_array is now ["some element"]
// #end::mutateInArrayDoc[]

// #tag::mutateInArrayDocMulti[]
$result = $collection->mutateIn("my_array", [
    new \Couchbase\MutateArrayAppendSpec("", ["elem1", "elem2", "elem3"])
]);
// the document my_array is now ["some_element", "elem1", "elem2", "elem3"]
// #end::mutateInArrayDocMulti[]

// #tag::mutateInArrayDocMultiSingle[]
$result = $collection->mutateIn("my_array", [
    new \Couchbase\MutateArrayAppendSpec("", [["elem1", "elem2", "elem3"]])
]);
// the document my_array is now ["some_element", ["elem1", "elem2", "elem3"]]
// #end::mutateInArrayDocMultiSingle[]

// #tag::mutateInArrayAppendMulti[]
$result = $collection->mutateIn("my_array", [
    new \Couchbase\MutateArrayAppendSpec("", ["elem1"]),
    new \Couchbase\MutateArrayAppendSpec("", ["elem2"]),
    new \Couchbase\MutateArrayAppendSpec("", ["elem3"]),
]);
// #end::mutateInArrayAppendMulti[]

// #tag::mutateInArrayAppendCreatePath[]
$result = $collection->mutateIn("customer123", [
    new \Couchbase\MutateArrayAppendSpec("some.array", ["Hello", "World"], false, true)
    ]);
// #end::mutateInArrayAppendCreatePath[]

// #tag::mutateInArrayAddUnique[]
$result = $collection->mutateIn("customer123", [
    new \Couchbase\MutateArrayAddUniqueSpec("purchases.complete", 95)
]);
// => Success

$result = $collection->mutateIn("customer123", [
    new \Couchbase\MutateArrayAddUniqueSpec("purchases.complete", 95)
]);
// => SubdocPathExists exception!
// #end::mutateInArrayAddUnique[]

// #tag::mutateInArrayInsert[]
$result = $collection->upsert("array", ["Hello", "world"]);
$result = $collection->mutateIn("array", [
    new \Couchbase\MutateArrayInsertSpec("[1]", ["cruel"])
]);
// #end::mutateInArrayInsert[]