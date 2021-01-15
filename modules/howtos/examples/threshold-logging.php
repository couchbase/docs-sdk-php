<?php

declare(strict_types=1);

/*
 * Make sure log level is high enough to display tracing messages
 */
// tag::logLevel[]
ini_set("couchbase.log_level", "INFO");
// end::logLevel[]

use Couchbase\Cluster;
use Couchbase\ClusterOptions;
use Couchbase\TimeoutException;
use Couchbase\UpsertOptions;

$options = new ClusterOptions();
$options->credentials("Administrator", "password");

// tag::thresholdLogging[]
$connectionString = "couchbase://127.0.0.1?" .
    "tracing_threshold_queue_flush_interval=3&" . /* every 3 seconds */
    "tracing_threshold_kv=0.01"; /* 10 milliseconds */

$cluster = new Cluster($connectionString, $options);
$bucket = $cluster->bucket("travel-sample");
$collection = $bucket->defaultCollection();
// end::thresholdLogging[]

// tag::thresholdLongProcessing[]
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
// end::thresholdLongProcessing[]

/*
 * Threshold reports will be written like the following
 *
 * [cb,INFO] (tracer L:149 I:2929787644) Operations over threshold: {"count":14,"service":"kv","top":[{"operation_name":"php/upsert","total_us":537133},{"operation_name":"php/upsert","total_us":513483},{"operation_name":"php/upsert","total_us":510245},{"operation_name":"php/upsert","total_us":500094},{"last_local_address":"127.0.0.1:41210","last_local_id":"aa562ed8aea102fc/a4a9305660272565","last_operation_id":"0x3","last_remote_address":"127.0.0.1:11210","operation_name":"upsert","server_us":150315,"total_us":320528},{"last_local_address":"127.0.0.1:41210","last_local_id":"aa562ed8aea102fc/a4a9305660272565","last_operation_id":"0x2","last_remote_address":"127.0.0.1:11210","operation_name":"upsert","server_us":126118,"total_us":317381},{"last_local_address":"127.0.0.1:41210","last_local_id":"aa562ed8aea102fc/a4a9305660272565","last_operation_id":"0x4","last_remote_address":"127.0.0.1:11210","operation_name":"upsert","server_us":149572,"total_us":311246},{"operation_name":"php/request_encoding","total_us":200289}]}
 */
