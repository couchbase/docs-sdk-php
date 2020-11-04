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

// #tag::mutateInUpsert[]
$result = $collection->mutateIn("customer123", [
    new \Couchbase\MutateUpsertSpec("fax", "311-555-0151")
]);
// #end::mutateInUpsert[]

// #tag::mutateInInsert[]
$result = $collection->mutateIn("customer123", [
    new \Couchbase\MutateInsertSpec("purchases.complete", [42, true, "None"])
]);
// SubdocPathExistsError
// #end::mutateInInsert[]

// #tag::mutateInMulti[]
$result = $collection->mutateIn("customer123", [
    new \Couchbase\MutateRemoveSpec("addresses.billing"),
    new \Couchbase\MutateReplaceSpec("email", "dougr96@hotmail.com")
]);
// #end::mutateInMulti[]

// #tag::mutateInCreatePath[]
$result = $collection->mutateIn("customer123", [
    new \Couchbase\MutateUpsertSpec("level_0.level_1.foo.bar.phone",
                                    ["num" => "311-555-0101", "ext" => 16],
                                    false, true)
]);
// #end::mutateInCreatePath[]

// #tag::mutateInCas[]
$doc = $collection->get("customer123");
$options = new \Couchbase\MutateInOptions();
$options->cas($doc->cas());

$res = $collection->mutatein("customer123", [
    new \Couchbase\MutateArrayAppendSpec("purchases.complete", [1000])
], $options);
// #end::mutateInCas[]