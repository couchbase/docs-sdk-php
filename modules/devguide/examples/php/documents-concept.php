<?php

use Couchbase\ClusterOptions;
use Couchbase\IncrementOptions;
use Couchbase\DecrementOptions;
use Couchbase\ReplaceOptions;
use Couchbase\Cluster;
use Couchbase\MutateUpsertSpec;
use Couchbase\LookupGetSpec;

$connectionString = 'localhost';
$clusterOpts = new ClusterOptions();
$clusterOpts->credentials('Administrator', 'password');

$cluster = new Cluster($connectionString, $clusterOpts);
$bucket = $cluster->bucket('travel-sample');
$collection = $bucket->scope('inventory')->collection('airline');

print("Example - [mutate-in]\n");
// tag::mutate-in[]
$result = $collection->mutateIn('airline_10', [
    new MutateUpsertSpec('msrp', 18.00)
]);
// end::mutate-in[]

print("Example - [lookup-in]\n");
// tag::lookup-in[]
$usersCollection = $bucket->scope('tenant_agent_00')->collection('users');
$usersCollection->lookupIn('1', [
    new LookupGetSpec('credit_cards[0].type'),
    new LookupGetSpec('credit_cards[0].expiration')
]);
// end::lookup-in[]

print("Example - [counters]\n");
// tag::counters[]
$counterDocId = 'counter-doc';
$decrementOpts = new DecrementOptions();
$incrementOpts = new IncrementOptions();
// Increment by 1, creating doc if needed.
// By using `initial(1)` we set the starting count(non-negative) to 1 if the document needs to be created.
// If it already exists, the count will increase by 1.
$collection->binary()->increment($counterDocId, $incrementOpts->initial(1));
// Decrement by 1
$collection->binary()->decrement($counterDocId);
// Decrement by 5
$collection->binary()->decrement($counterDocId, $decrementOpts->delta(5));
// end::counters[]

print("Example - [counter-increment]\n");
// tag::counter-increment[]
$result = $collection->get('counter-doc');
$value = $result->content();
$incrementAmnt = 5;

if (shouldIncrementValue($value)) {
    $opts = new ReplaceOptions();
    $opts->cas($result->cas());
    $collection->replace('counter-doc', $value + $incrementAmnt, $opts);
}
// end::counter-increment[]
printf("RESULT: %d", $value + $incrementAmnt);

function shouldIncrementValue($value): bool {
    printf("Current value: %d\n", $value);
    return $value == 0;
}
