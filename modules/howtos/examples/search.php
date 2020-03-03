<?php
use \Couchbase\ClusterOptions;
use \Couchbase\Cluster;
use \Couchbase\MatchSearchQuery;
use \Couchbase\NumericRangeSearchQuery;
use \Couchbase\ConjunctionSearchQuery;
use \Couchbase\SearchOptions;
use \Couchbase\MutationState;

/*
 * index definition
// #tag::indexdefinition[]
{
  "type": "fulltext-index",
  "name": "travel-sample-index",
  "uuid": "ea630dfe35e1f415",
  "sourceType": "couchbase",
  "sourceName": "travel-sample",
  "sourceUUID": "8ee9d874356f4c92a63a244f5e34210a",
  "planParams": {
    "maxPartitionsPerPIndex": 171,
    "indexPartitions": 6
  },
  "params": {
    "doc_config": {
      "docid_prefix_delim": "",
      "docid_regexp": "",
      "mode": "type_field",
      "type_field": "type"
    },
    "mapping": {
      "analysis": {},
      "default_analyzer": "standard",
      "default_datetime_parser": "dateTimeOptional",
      "default_field": "_all",
      "default_mapping": {
        "dynamic": true,
        "enabled": true
      },
      "default_type": "_default",
      "docvalues_dynamic": true,
      "index_dynamic": true,
      "store_dynamic": false,
      "type_field": "_type"
    },
    "store": {
      "indexType": "scorch"
    }
  },
  "sourceParams": {}
}
// #end::indexdefinition[]
 */
$opts = new ClusterOptions();
$opts->credentials("Administrator", "password");
$cluster = new Cluster("couchbase://192.168.1.101", $opts);

// #tag::matchquery[]
$matchQuery = new MatchSearchQuery("swanky");
$matchQuery->field("reviews.content");
$opts = new SearchOptions();
$opts->limit(10);
$res = $cluster->searchQuery("travel-sample-index", $matchQuery, $opts);
printf("Match query: \"swanky\":\n");
foreach ($res->rows() as $row) {
    printf("id: %s, score: %f\n", $row['id'], $row['score']);
}
// #end::matchquery[]

// #tag::numrangequery[]
$numericRangeQuery = new NumericRangeSearchQuery();
$numericRangeQuery->field("reviews.ratings.Cleanliness")->min(5);
$opts = new SearchOptions();
$opts->limit(10);
$res = $cluster->searchQuery("travel-sample-index", $numericRangeQuery, $opts);
printf("Cleanliness 5+:\n");
foreach ($res->rows() as $row) {
    printf("id: %s, score: %f\n", $row['id'], $row['score']);
}
// #end::numrangequery[]

// #tag::conjunctionquery[]
$conjunction = new ConjunctionSearchQuery([$matchQuery, $numericRangeQuery]);
$opts = new SearchOptions();
$opts->limit(10);
$res = $cluster->searchQuery("travel-sample-index", $conjunction, $opts);
printf("Swanky and with cleanliness 5+:\n");
foreach ($res->rows() as $row) {
    printf("id: %s, score: %f\n", $row['id'], $row['score']);
}
// #end::conjunctionquery[]


// #tag::consistency[]
// Create new hotel document and demonstrate query with consistency requirement
$collection = $cluster->bucket('travel-sample')->defaultCollection();
$hotel = [
    "name" => "super hotel",
    "reviews" => [
        [
            "content" => "Super swanky hotel!",
            "ratings" => [
                "Cleanliness" => 6
            ]
        ]
    ]
];
$res = $collection->upsert("a-new-hotel", $hotel);
$mutationState = new MutationState();
$mutationState->add($res);
$opts = new SearchOptions();
$opts->limit(10);
$opts->consistentWith("travel-sample-index", $mutationState);
$res = $cluster->searchQuery("travel-sample-index", $matchQuery, $opts);
printf("Match query: \"swanky\":\n");
foreach ($res->rows() as $row) {
    printf("id: %s, score: %f\n", $row['id'], $row['score']);
}
// #end::consistency[]

// Output
//
//     Match query: "swanky":
//     id: hotel_25794, score: 0.541554
//     id: hotel_25800, score: 0.511521
//     id: hotel_25598, score: 0.510087
//     id: hotel_16350, score: 0.480130
//     id: hotel_25301, score: 0.418002
//     Cleanliness 5+:
//     id: hotel_5335, score: 1.220367
//     id: hotel_21673, score: 1.220367
//     id: hotel_26139, score: 1.220367
//     id: hotel_635, score: 1.220367
//     id: hotel_21665, score: 1.220367
//     id: hotel_21679, score: 1.220367
//     id: hotel_15978, score: 1.220367
//     id: hotel_35667, score: 1.220367
//     id: hotel_4397, score: 1.220367
//     id: hotel_2241, score: 1.220367
//     Swanky and with cleanliness 5+:
//     id: hotel_16350, score: 1.005243
//     id: hotel_25800, score: 0.900247
//     id: hotel_25301, score: 0.792935
//     id: hotel_25794, score: 0.534181
//     Match query: "swanky":
//     id: a-new-hotel, score: 4.884002
//     id: hotel_25794, score: 0.541554
//     id: hotel_25800, score: 0.511521
//     id: hotel_25598, score: 0.510087
//     id: hotel_16350, score: 0.480130
//     id: hotel_25301, score: 0.418002

