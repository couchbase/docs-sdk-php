<?php

declare(strict_types=1);

/*
 * Make sure log level is high enough to display tracing messages
 */
ini_set("couchbase.log_level", "INFO");

use Couchbase\Cluster;
use Couchbase\ClusterOptions;
use Couchbase\TimeoutException;
use Couchbase\UpsertOptions;

$options = new ClusterOptions();
$options->credentials("Administrator", "password");

// tag::orphanLogging[]
$connectionString = "couchbase://127.0.0.1?" .
    "tracing_orphaned_queue_flush_interval=5&"; /* every 5 seconds */

$cluster = new Cluster($connectionString, $options);
$bucket = $cluster->bucket("travel-sample");
$collection = $bucket->defaultCollection();
// end::orphanLogging[]

// tag::orphanTimeout[]
/*
 * Create a new document
 */
$document = ["answer" => 42, "updated_at" => date("r")];
$collection->upsert("foo", $document);

/*
 * Replace the document with a big body and very small deadline which should trigger a 
 * client-side timeout, in which case the server response will be reported as orphan
 */
$options = new UpsertOptions();
$options->timeout(1);
/*
 * Generate a document with 10M payload, that should be unfriendly to the compressing function
 * and longer to process on the server side
 */
$document = ["noise" => base64_encode(random_bytes(15_000_000))];
$numberOfTimeouts = 0;
while (true) {
    try {
        $collection->upsert("foo", $document, $options);
    } catch (TimeoutException $e) {
        $numberOfTimeouts++;
        if ($numberOfTimeouts > 3) {
            break;
        }
    }
}
// end::orphanTimeout[]

/*
 * Messages like one below will appear in the log for the orphaned response
 *
 * [cb,WARN] (tracer L:147 I:2929787644) Orphan responses observed: {"count":2,"service":"kv","top":[{"last_local_address":"127.0.0.1:41210","last_local_id":"aa562ed8aea102fc/a4a9305660272565","last_operation_id":"0x11","last_remote_address":"127.0.0.1:11210","operation_name":"upsert","server_us":0,"total_us":34904},{"last_local_address":"127.0.0.1:41210","last_local_id":"aa562ed8aea102fc/a4a9305660272565","last_operation_id":"0xb","last_remote_address":"127.0.0.1:11210","operation_name":"upsert","server_us":0,"total_us":32195}]}
 */
