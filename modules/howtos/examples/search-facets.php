<?php
use \Couchbase\ClusterOptions;
use \Couchbase\Cluster;
use \Couchbase\TermSearchQuery;
use \Couchbase\SearchOptions;
use \Couchbase\TermSearchFacet;
use \Couchbase\DateRangeSearchFacet;
use \Couchbase\NumericRangeSearchFacet;

$opts = new ClusterOptions();
$opts->credentials("Administrator", "password");
$cluster = new Cluster("couchbase://192.168.1.101", $opts);

// #tag:iteratingfacets
$query = (new TermSearchQuery("beer"))->field("type");
$options = new SearchOptions();
$options->facets([
    "foo" => new TermSearchFacet("name", 3),
    "bar" => (new DateRangeSearchFacet("updated", 1))
                ->addRange("old", NULL,  mktime(0, 0, 0, 1, 1, 2014)), // "2014-01-01T00:00:00" also acceptable
    "baz" => (new NumericRangeSearchFacet("abv", 2))
                ->addRange("strong", 4.9, NULL)
                ->addRange("light", NULL, 4.89)
]);
$res = $cluster->searchQuery("beer-search", $query, $options);

$facet = $res->facets()["foo"];
printf("Term facet \"foo\" on field \"%s\". Total: %d, missing: %d: other: %d\n",
    $facet["field"], $facet["total"], $facet["missing"], $facet["other"]);
foreach ($facet["terms"] as $term) {
    printf(" * %-5s ... %d\n", $term["term"], $term["count"]);
}

$facet = $res->facets()["bar"];
printf("Date range facet \"bar\" on field \"%s\". Total: %d, missing: %d: other: %d\n",
    $facet["field"], $facet["total"], $facet["missing"], $facet["other"]);
foreach ($facet["date_ranges"] as $range) {
    printf(" * %-20s ... %d\n", $range["end"], $range["count"]);
}

$facet = $res->facets()["baz"];
printf("Numeric range facet \"baz\" on field \"%s\". Total: %d, missing: %d: other: %d\n",
    $facet["field"], $facet["total"], $facet["missing"], $facet["other"]);
foreach ($facet["numeric_ranges"] as $range) {
    if (isset($range["max"])) {
        printf(" * max %-4s ... %d\n", $range["max"], $range["count"]);
    } else {
        printf(" * min %-4s ... %d\n", $range["min"], $range["count"]);
    }
}
// #end:iteratingfacets

// Output
//
//    Term facet "foo" on field "name". Total: 15260, missing: 0: other: 13000
//     * ale   ... 1421
//     * stout ... 432
//     * pale  ... 407
//    Date range facet "bar" on field "updated". Total: 5891, missing: 0: other: 0
//     * 2014-01-01T00:00:00Z ... 5891
//    Numeric range facet "baz" on field "abv". Total: 5891, missing: 0: other: 0
//     * max 4.89 ... 3386
//     * min 4.9  ... 2505


/*
 * index definition
{
  "type": "fulltext-index",
  "name": "beer-search",
  "uuid": "5f58f6e660a5ebea",
  "sourceType": "couchbase",
  "sourceName": "beer-sample",
  "sourceUUID": "37838ef14de076784cc5b49b17682e0d",
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
      "type_field": "_type",
      "types": {
        "beer": {
          "dynamic": true,
          "enabled": true,
          "properties": {
            "abv": {
              "dynamic": false,
              "enabled": true,
              "fields": [
                {
                  "docvalues": true,
                  "include_in_all": true,
                  "include_term_vectors": true,
                  "index": true,
                  "name": "abv",
                  "store": true,
                  "type": "number"
                }
              ]
            },
            "category": {
              "dynamic": false,
              "enabled": true,
              "fields": [
                {
                  "docvalues": true,
                  "include_in_all": true,
                  "include_term_vectors": true,
                  "index": true,
                  "name": "category",
                  "store": true,
                  "type": "text"
                }
              ]
            },
            "description": {
              "dynamic": false,
              "enabled": true,
              "fields": [
                {
                  "docvalues": true,
                  "include_in_all": true,
                  "include_term_vectors": true,
                  "index": true,
                  "name": "description",
                  "store": true,
                  "type": "text"
                }
              ]
            },
            "name": {
              "dynamic": false,
              "enabled": true,
              "fields": [
                {
                  "docvalues": true,
                  "include_in_all": true,
                  "include_term_vectors": true,
                  "index": true,
                  "name": "name",
                  "store": true,
                  "type": "text"
                }
              ]
            },
            "style": {
              "dynamic": false,
              "enabled": true,
              "fields": [
                {
                  "docvalues": true,
                  "include_in_all": true,
                  "include_term_vectors": true,
                  "index": true,
                  "name": "style",
                  "store": true,
                  "type": "text"
                }
              ]
            },
            "updated": {
              "dynamic": false,
              "enabled": true,
              "fields": [
                {
                  "docvalues": true,
                  "include_in_all": true,
                  "include_term_vectors": true,
                  "index": true,
                  "name": "updated",
                  "store": true,
                  "type": "datetime"
                }
              ]
            }
          }
        }
      }
    },
    "store": {
      "indexType": "scorch"
    }
  },
  "sourceParams": {}
}
 */
