# Changelog

## [1.33.0](https://github.com/webmappsrl/osmfeatures/compare/v1.32.0...v1.33.0) (2025-02-05)


### Features

* added map components to nova resources (admin areas, places, hiking routes, poles) ([d462b74](https://github.com/webmappsrl/osmfeatures/commit/d462b743bf47f3cade0126c1ef56057adae01b20))
* added post endpoint for admin areas geojson ([2447ae3](https://github.com/webmappsrl/osmfeatures/commit/2447ae315bbe85cde2bfea4e17bec1c1967f192d))
* ask for a confirmation on sm full pbf import oc:4625 ([ca769fd](https://github.com/webmappsrl/osmfeatures/commit/ca769fd08162da6807c13cf7beba4d0dae4ce934))
* configured log viewer ([472b70a](https://github.com/webmappsrl/osmfeatures/commit/472b70a48c3405227f2703fcea988c2222ad541b))
* enable xdebug code coverage feature oc:4354 ([a2a30e3](https://github.com/webmappsrl/osmfeatures/commit/a2a30e39fcc7014ed6af005c7b3696b451403d2b))
* features collection geojson download action OC:3720 ([#138](https://github.com/webmappsrl/osmfeatures/issues/138)) ([ade8a78](https://github.com/webmappsrl/osmfeatures/commit/ade8a7898f88243d15a2b9f3ae604f42f0392d30))
* features xls export OC:3811 ([#137](https://github.com/webmappsrl/osmfeatures/issues/137)) ([8b7db2c](https://github.com/webmappsrl/osmfeatures/commit/8b7db2cead29ff923c499b796ec25ef94ffc2784))
* move the wm-package as submodule ([fea50e7](https://github.com/webmappsrl/osmfeatures/commit/fea50e7a1ba964df8de1a32fd15c08e0fe754218))


### Bug Fixes

* added osm2cai status query parameter to hiking routes list api ([77ab216](https://github.com/webmappsrl/osmfeatures/commit/77ab21680fb220bf17566e4f5a461449437c7cb8))
* change command name oc:4625 ([82f9390](https://github.com/webmappsrl/osmfeatures/commit/82f9390032ba8d778dd46630331ed991fc69f045))
* change command name2 oc:4625 ([58e93e1](https://github.com/webmappsrl/osmfeatures/commit/58e93e112a2ec9b49b01314eab19dfeceb74607a))
* change command name3 oc:4625 ([68bdf65](https://github.com/webmappsrl/osmfeatures/commit/68bdf65121a1db098759f528bf70a11b2ed7fbac))
* db name on sql import oc:4625 ([13410a5](https://github.com/webmappsrl/osmfeatures/commit/13410a515720ac7b8c136ea286fbfedbbe8d95b5))
* dem field missing in hiking route OC:3809 ([91e650e](https://github.com/webmappsrl/osmfeatures/commit/91e650ed5ece7fb5422601ad362a5831d409510b))
* fixed admin area api tests ([9fcab4c](https://github.com/webmappsrl/osmfeatures/commit/9fcab4c65134bdcf44aa96fd8a29b12752b3f40a))
* installed jwt dependency in root project ([50230a4](https://github.com/webmappsrl/osmfeatures/commit/50230a4843b1f73dd6d263dd75969304a8743ccd))
* pbf rewrite oc:4625 ([bc7cad6](https://github.com/webmappsrl/osmfeatures/commit/bc7cad6eb36d3c216a174a339f21ea8861aa811c))
* refactor of pbf import procedure oc:4625 ([2058ec9](https://github.com/webmappsrl/osmfeatures/commit/2058ec9f3e56712cc258a183b2606834ccadaccb))
* running tests on github actions ([fe61eaf](https://github.com/webmappsrl/osmfeatures/commit/fe61eaf79cee6132b56c56a0da93224aa4e1e352))
* running tests on github actions2 oc: 4379 ([446c1c5](https://github.com/webmappsrl/osmfeatures/commit/446c1c592f5249872641f459047e5bcb70555890))
* typo in poiController swagger notation ([2086ed5](https://github.com/webmappsrl/osmfeatures/commit/2086ed53e0cbe22aad8e635dfd6164db40cba13b))
* wm-package version in composer json ([7fdc0b9](https://github.com/webmappsrl/osmfeatures/commit/7fdc0b9dc443495704e36d96f99cc082204a037c))
* wmdumps filesystem ([e8ccecd](https://github.com/webmappsrl/osmfeatures/commit/e8ccecd914e8b1ab11061580cb00352d51a3cda9))


### Miscellaneous Chores

* admin_areas_enrichments nova filter ([cf5d61f](https://github.com/webmappsrl/osmfeatures/commit/cf5d61ffd5321f42622219478a009d5fe7d28571))
* fix a command typo oc:4625 ([2dbf531](https://github.com/webmappsrl/osmfeatures/commit/2dbf5313f4cbe68a0471c6f8c3e0b43f1b88b0be))
* improved data validation for post request ([fb5863a](https://github.com/webmappsrl/osmfeatures/commit/fb5863a09983d0ba507e8c48b002ce00402c74a9))
* improved enrichments recovery and check update commands ([8ee2dd6](https://github.com/webmappsrl/osmfeatures/commit/8ee2dd6cccd10bab66633d3dfa29c15bbe0abed1))
* installed log viewer package ([5642e61](https://github.com/webmappsrl/osmfeatures/commit/5642e61453fc108ad2f38afe3e7be04f3bdf077f))
* removed deprecated pois api from documentation ([fac0c46](https://github.com/webmappsrl/osmfeatures/commit/fac0c4622e4c85d3bf1e82dddf1aaac6d6a43ce0))
* swagger notation refactoring ([8ea1074](https://github.com/webmappsrl/osmfeatures/commit/8ea10744cd1a4499e3cf029e5e5fda994bf0008f))
* tests for new api endpoint ([1858fcc](https://github.com/webmappsrl/osmfeatures/commit/1858fcc521ef1905bf00ab2a295e4dc5bb21e877))
* updated actions/upload-artifact to [@v4](https://github.com/v4) ([b342f10](https://github.com/webmappsrl/osmfeatures/commit/b342f10c2ae413c29ee13fa6eebbdf9be86ecb5a))
* upload to aws testing command (to insert in cron job production) ([0066e90](https://github.com/webmappsrl/osmfeatures/commit/0066e90aa346698f165daf517ceb3f2342b1ca8c))

## [1.32.0](https://github.com/webmappsrl/osmfeatures/compare/v1.31.0...v1.32.0) (2024-08-20)


### Features

* add dem info to hikingroute OC:3740 ([#127](https://github.com/webmappsrl/osmfeatures/issues/127)) ([e0e3f75](https://github.com/webmappsrl/osmfeatures/commit/e0e3f75151cb150711597472ec018f1c1558923e))
* add_admin_areas_to_hiking_routes OC:3762 ([#129](https://github.com/webmappsrl/osmfeatures/issues/129)) ([bf8376e](https://github.com/webmappsrl/osmfeatures/commit/bf8376efefd71ddd408c252ed43ab6abbc91c0e6))
* added check dem enrichments update command ([9dccb69](https://github.com/webmappsrl/osmfeatures/commit/9dccb69dc8da1b9eed6d3a7509a46a10d028251a))
* change_geometry_projection_to_4326 OC:3779 ([#130](https://github.com/webmappsrl/osmfeatures/issues/130)) ([9895140](https://github.com/webmappsrl/osmfeatures/commit/98951407c9b0624fc1b41a44d4f357d81121808d))
* hiking_routes_admin_area_auto_update OC:3800 ([#132](https://github.com/webmappsrl/osmfeatures/issues/132)) ([9144811](https://github.com/webmappsrl/osmfeatures/commit/9144811ee3b77a854f28b00c83172b85c8cec9a6))
* supervisor integration in docker OC: 3690 ([0749997](https://github.com/webmappsrl/osmfeatures/commit/0749997f11d8af6224f64282b831d53f858b2f80))
* supervisor integration with docker ([f309d35](https://github.com/webmappsrl/osmfeatures/commit/f309d35139f6026b1ec8a6ba99373081ef806c50))


### Bug Fixes

* added srid transform 4326 to controllers ([8f25320](https://github.com/webmappsrl/osmfeatures/commit/8f253208818ea4e7f27ecc6d187fd3275ba65077))
* check dem enrichments update command ([7335fea](https://github.com/webmappsrl/osmfeatures/commit/7335fea229abc95c756440b40e4eb5e841e3862e))
* fixed admin areas and user link nova ([1340fec](https://github.com/webmappsrl/osmfeatures/commit/1340fecb7c1e6b990badfb9ab82b20f403090e56))
* fixed docker supervisor configuration ([3c988d7](https://github.com/webmappsrl/osmfeatures/commit/3c988d7939a773b79c0e1b663dfc48b5f1e15ca6))
* srid change tests ([048c0ed](https://github.com/webmappsrl/osmfeatures/commit/048c0ed70599bd306f7224a95c633071445843d2))


### Miscellaneous Chores

* added has invalid geometry flag for hiking routes and added nova filter ([4c1a65e](https://github.com/webmappsrl/osmfeatures/commit/4c1a65e1ddb0c7610a400c82762d39d5bcbbec05))
* added progress bar to dem enrichment command ([1d572b2](https://github.com/webmappsrl/osmfeatures/commit/1d572b22d4599c46c072f2fda717f93ca66de101))
* new docker horizon and supervisor configuration ([a55ae99](https://github.com/webmappsrl/osmfeatures/commit/a55ae99dc93591c9e11e07270cef33842f82085b))
* srid change tests ([ca81641](https://github.com/webmappsrl/osmfeatures/commit/ca816411c4cb28fbcd15e7764aea12d78bba3df8))
* updated prod deploy pipeline to use horizon:terminate command ([74440ca](https://github.com/webmappsrl/osmfeatures/commit/74440ca05e31f39ef96e01bac30050e195ff8893))

## [1.31.0](https://github.com/webmappsrl/osmfeatures/compare/v1.30.1...v1.31.0) (2024-07-16)


### Features

* Api Places distance from coordinates OC:3628 ([#121](https://github.com/webmappsrl/osmfeatures/issues/121)) ([c8a26ea](https://github.com/webmappsrl/osmfeatures/commit/c8a26eaf9daa668528ca0024b427a0d71f768359))
* Images from wikipedia and wikidata OC: 3630 ([#122](https://github.com/webmappsrl/osmfeatures/issues/122)) ([d3d7dff](https://github.com/webmappsrl/osmfeatures/commit/d3d7dffd1e441fa5f97ab0c83b4e7024df1110b0))


### Bug Fixes

* fixed openai enrichment to get existing abstract and description if response is not valid OC:3583 ([bc1d7b9](https://github.com/webmappsrl/osmfeatures/commit/bc1d7b9330d69bc8a5a6ac15b6b72c360fc8124d))
* wikidata redirect OC:3563 ([#120](https://github.com/webmappsrl/osmfeatures/issues/120)) ([9633ac6](https://github.com/webmappsrl/osmfeatures/commit/9633ac6bd479e3b52c6da34560a15cca1d9ad77f))

## [1.30.1](https://github.com/webmappsrl/osmfeatures/compare/v1.30.0...v1.30.1) (2024-07-01)


### Bug Fixes

* bug fixes ([285c3fa](https://github.com/webmappsrl/osmfeatures/commit/285c3fa48a4432522029cf929ee1031817b64ecb))

## [1.30.0](https://github.com/webmappsrl/osmfeatures/compare/v1.29.0...v1.30.0) (2024-06-28)


### Features

* extended osmfeatures nova resource to admin areas, poles and hiking routes ([08b93bd](https://github.com/webmappsrl/osmfeatures/commit/08b93bdd98cbe07462ec4511cd85f2e0a1951d10))

## [1.29.0](https://github.com/webmappsrl/osmfeatures/compare/v1.28.1...v1.29.0) (2024-06-28)


### Features

* added enrichment job action ([9337039](https://github.com/webmappsrl/osmfeatures/commit/93370393241f948833c58a174506378da22d0f73))
* improved tags field in nova places ([1f9ed7b](https://github.com/webmappsrl/osmfeatures/commit/1f9ed7bfaf281b45a252f2b7bdcb32e38013302f))


### Bug Fixes

* fixed bug in wikimedia service ([ce8ac96](https://github.com/webmappsrl/osmfeatures/commit/ce8ac9692b6460651ced4f75a8ea8b5c0009928a))
* fixed content generation based on wikis data ([ee44e03](https://github.com/webmappsrl/osmfeatures/commit/ee44e03cfa79449ec97518064ea0a6bd39c0dc13))


### Miscellaneous Chores

* error handling ([e515ed3](https://github.com/webmappsrl/osmfeatures/commit/e515ed3157c5adb22c932e95cc793fb22c8fb731))
* refactored enrichment command ([31b1e83](https://github.com/webmappsrl/osmfeatures/commit/31b1e83ab1de6d60da6a08bc7e208f88f0d4ef72))

## [1.28.1](https://github.com/webmappsrl/osmfeatures/compare/v1.28.0...v1.28.1) (2024-06-27)


### Bug Fixes

* update method call in EnrichmentService ([1a4a354](https://github.com/webmappsrl/osmfeatures/commit/1a4a354cd8bdbae1a9a3f78a3931fc12273aaf60))

## [1.28.0](https://github.com/webmappsrl/osmfeatures/compare/v1.27.0...v1.28.0) (2024-06-27)


### Features

* Add optional height specification to Wikimedia image data retrieval ([f5d3b0e](https://github.com/webmappsrl/osmfeatures/commit/f5d3b0eebd6ac21c7d3c2091cd818c50bc9574a2))
* Improve image handling in Place and WikimediaService ([e15d100](https://github.com/webmappsrl/osmfeatures/commit/e15d10073c0ba4c4b157418c41572d2f2dd0fdb9))

## [1.27.0](https://github.com/webmappsrl/osmfeatures/compare/v1.26.0...v1.27.0) (2024-06-27)


### Features

* added enrichments filter to place nova ([4473c52](https://github.com/webmappsrl/osmfeatures/commit/4473c528148f80258e8f9cd691852a47de5374c6))

## [1.26.0](https://github.com/webmappsrl/osmfeatures/compare/v1.25.0...v1.26.0) (2024-05-09)


### Features

* updated places lua ([ade5c01](https://github.com/webmappsrl/osmfeatures/commit/ade5c01832ef2267c7a538f6f7984ebc217edae3))

## [1.25.0](https://github.com/webmappsrl/osmfeatures/compare/v1.24.0...v1.25.0) (2024-05-09)


### Features

* updated places mapping in lua script ([3e0c503](https://github.com/webmappsrl/osmfeatures/commit/3e0c5033e4673e21ba1e65d85a03d3400d49f576))

## [1.24.0](https://github.com/webmappsrl/osmfeatures/compare/v1.23.1...v1.24.0) (2024-05-08)


### Features

* updated mapping for places lua ([ab97729](https://github.com/webmappsrl/osmfeatures/commit/ab97729c9c6b50e7e07d4bc3e02b91d619cabdc0))

## [1.23.1](https://github.com/webmappsrl/osmfeatures/compare/v1.23.0...v1.23.1) (2024-05-02)


### Bug Fixes

* fixed wiki links ([624bfe8](https://github.com/webmappsrl/osmfeatures/commit/624bfe8d1e92421f1659a4a05e1455d3e638caae))

## [1.23.0](https://github.com/webmappsrl/osmfeatures/compare/v1.22.0...v1.23.0) (2024-05-02)


### Features

* increased api throttle ([8ad8c8a](https://github.com/webmappsrl/osmfeatures/commit/8ad8c8a13d309b7e2fdff0cea84b6692527f7987))

## [1.22.0](https://github.com/webmappsrl/osmfeatures/compare/v1.21.1...v1.22.0) (2024-04-30)


### Features

* added tests for osmfeaturesidprocessor trait ([8bac21e](https://github.com/webmappsrl/osmfeatures/commit/8bac21e74db649040f11eacd129d7a3dc4d300eb))
* deleted osmtype apis ([e609d40](https://github.com/webmappsrl/osmfeatures/commit/e609d40e4fa55397c7929c7d43849ffcd025e235))
* improved api tests ([7270e8a](https://github.com/webmappsrl/osmfeatures/commit/7270e8ae88c250a3f5801f32854952c69fe43eff))
* updated api documentation ([fbb6e35](https://github.com/webmappsrl/osmfeatures/commit/fbb6e35c7ccbd4c2629ea83e7eb3636393608376))
* updated list apis to provide osmfeatures id ([f2f6c6f](https://github.com/webmappsrl/osmfeatures/commit/f2f6c6f1db9447515a2f255c37845346a68bff41))
* updated single feature apis to accept osmfeatures_id as parameter ([09f77ac](https://github.com/webmappsrl/osmfeatures/commit/09f77acfcf6745fff6beaa0b390da8735ad0cce0))
* updated tests ([20431d5](https://github.com/webmappsrl/osmfeatures/commit/20431d522c62ef8f44dffb01490669d0b00d65ae))

## [1.21.1](https://github.com/webmappsrl/osmfeatures/compare/v1.21.0...v1.21.1) (2024-04-29)


### Bug Fixes

* added is monitored trait to job ([8a50dcc](https://github.com/webmappsrl/osmfeatures/commit/8a50dcc5ced2c464a4acbd49e751d192010e4881))

## [1.21.0](https://github.com/webmappsrl/osmfeatures/compare/v1.20.5...v1.21.0) (2024-04-29)


### Features

* enhanced hiking_routes timestamp update job ([3dfe4ba](https://github.com/webmappsrl/osmfeatures/commit/3dfe4ba5e14b02295ecbcb49642bec4ae682999c))

## [1.20.5](https://github.com/webmappsrl/osmfeatures/compare/v1.20.4...v1.20.5) (2024-04-29)


### Bug Fixes

* fixed typo ([8d59e5a](https://github.com/webmappsrl/osmfeatures/commit/8d59e5a49202e386390d2f999e7fbe02cf5ea832))

## [1.20.4](https://github.com/webmappsrl/osmfeatures/compare/v1.20.3...v1.20.4) (2024-04-29)


### Bug Fixes

* fixed typo ([a6b48f3](https://github.com/webmappsrl/osmfeatures/commit/a6b48f30a951bbd68b401acdecf217c627253e21))

## [1.20.3](https://github.com/webmappsrl/osmfeatures/compare/v1.20.2...v1.20.3) (2024-04-29)


### Bug Fixes

* fixed geometry in hiking routes ways lua ([194319a](https://github.com/webmappsrl/osmfeatures/commit/194319abffdec896c1d64e0d800610ec4189899d))

## [1.20.2](https://github.com/webmappsrl/osmfeatures/compare/v1.20.1...v1.20.2) (2024-04-29)


### Bug Fixes

* separated hiking routes lua from hiking_routes_way lua ([c8ffbc4](https://github.com/webmappsrl/osmfeatures/commit/c8ffbc44f9259885141e3ab962ab482bb719a9e8))

## [1.20.1](https://github.com/webmappsrl/osmfeatures/compare/v1.20.0...v1.20.1) (2024-04-27)


### Bug Fixes

* fixed migration ([64f3329](https://github.com/webmappsrl/osmfeatures/commit/64f33296018c06d8ab719ed62a758fe54c5e959a))

## [1.20.0](https://github.com/webmappsrl/osmfeatures/compare/v1.19.0...v1.20.0) (2024-04-27)


### Features

* written tests for job ([42f73cf](https://github.com/webmappsrl/osmfeatures/commit/42f73cfe1ab6f7091e50d4f81872afba95e6de3f))


### Bug Fixes

* migration ([57ca4db](https://github.com/webmappsrl/osmfeatures/commit/57ca4db796f98d99dec474a5ff77d9143c926eff))

## [1.19.0](https://github.com/webmappsrl/osmfeatures/compare/v1.18.1...v1.19.0) (2024-04-27)


### Features

* hiking_routes updated_at enhancement ([4b33e96](https://github.com/webmappsrl/osmfeatures/commit/4b33e9682ce6dd8d17805877cf3c23cb1db43bd6))
* implemented job queue monitor ([29f864c](https://github.com/webmappsrl/osmfeatures/commit/29f864cd70be57f0a9bf63bebc3c6c784182f8e2))
* updated readme ([ff721bc](https://github.com/webmappsrl/osmfeatures/commit/ff721bc452ab4568d6413c37b773af9e7b5fac23))

## [1.18.1](https://github.com/webmappsrl/osmfeatures/compare/v1.18.0...v1.18.1) (2024-04-16)


### Bug Fixes

* fixed typo ([6a9b3c5](https://github.com/webmappsrl/osmfeatures/commit/6a9b3c5ffae9ae0408a4d3f5ec3e06660ecb03e0))

## [1.18.0](https://github.com/webmappsrl/osmfeatures/compare/v1.17.0...v1.18.0) (2024-04-15)


### Features

* added admin areas osm api endpoint. Added tests and updated api docs ([9b0bdb2](https://github.com/webmappsrl/osmfeatures/commit/9b0bdb2cb24bc3724cae16a5bcd7643ce9ddb30b))
* added hiking-routes osm api endpoint. Added tests and updated api doc ([960c545](https://github.com/webmappsrl/osmfeatures/commit/960c545b348a52bd954ed7e8daf32bbef8b338c4))
* added places osm api enpoint. Added tests and updated api docs ([058da89](https://github.com/webmappsrl/osmfeatures/commit/058da895ae3b898c731c0c5a55b9cbc58e19a1c7))
* added pole wiki fields to single feature api. updated documentation and tests ([e1a0453](https://github.com/webmappsrl/osmfeatures/commit/e1a0453fc03c57def560d88268ffe7b66a7bf148))
* added poles osm api endpoints. Added tests and updated api doc ([fe866da](https://github.com/webmappsrl/osmfeatures/commit/fe866da9613693d157e24b1b9241d847509934b4))
* added wiki field to single feature hiking routes api. updated documentation and tests ([1d309ab](https://github.com/webmappsrl/osmfeatures/commit/1d309abb48b93027057775e40a3915055f04d223))
* added wiki fields to admin areas single feature api. Updated documentation ([46c580b](https://github.com/webmappsrl/osmfeatures/commit/46c580b3fe49cd52d4462706408f16b8fa39a78b))
* added wiki links for places single feature api. updated tests and documentation. Updated osmtagsprocessor trait ([5730c2b](https://github.com/webmappsrl/osmfeatures/commit/5730c2bc90b6b206f053f51bb94b3afbaf8f20eb))
* single feature api test implemented ([7f1a9e8](https://github.com/webmappsrl/osmfeatures/commit/7f1a9e87eacf98fa53e5be68d769c93c8146af43))
* updated api doc ([b7fee29](https://github.com/webmappsrl/osmfeatures/commit/b7fee29c4214ad871961240bccf1cbbe465b0f07))


### Bug Fixes

* deleted double elevation value in pole api ([427934b](https://github.com/webmappsrl/osmfeatures/commit/427934b0442b9a06c4d80fc0af62ef7c518c4223))
* fixed updated_at format to iso8601 and fixed tests ([9e7d628](https://github.com/webmappsrl/osmfeatures/commit/9e7d628e736080bc1476020528aff6132498486c))

## [1.17.0](https://github.com/webmappsrl/osmfeatures/compare/v1.16.0...v1.17.0) (2024-04-13)


### Features

* added api menu to nova sidebar ([c8e98b7](https://github.com/webmappsrl/osmfeatures/commit/c8e98b73d3410fa3dcbe3eb69972afab11a0c64d))
* added example value for parameters in api documentation ([a30ade7](https://github.com/webmappsrl/osmfeatures/commit/a30ade745f225138aad4309e36696d9835172c01))
* added optional admin_level parameter to admin area list api. Updated tests ([846da88](https://github.com/webmappsrl/osmfeatures/commit/846da8841df93c191d1a9884d48d6934b98715d2))
* added score parameter to admin areas list api and updated api documentation and tests ([8e66bf9](https://github.com/webmappsrl/osmfeatures/commit/8e66bf961cdf8664d779feffa89491a9ee0d0453))
* updated list apis to include optional score parameter. Updated api documentation and tests ([3b6bd2a](https://github.com/webmappsrl/osmfeatures/commit/3b6bd2ae6c5736bfd0c0a14bbd8beb624ae657b6))


### Bug Fixes

* test fix v1 ([d098349](https://github.com/webmappsrl/osmfeatures/commit/d098349bf24ee51fa5beaf7ea24c41f8c766e195))

## [1.16.0](https://github.com/webmappsrl/osmfeatures/compare/v1.15.1...v1.16.0) (2024-04-12)


### Features

* added range filter for score column ([4c7a9a4](https://github.com/webmappsrl/osmfeatures/commit/4c7a9a4007bf3bb49b3366aa39909f7568a3a9b2))
* added score ([e5d4c98](https://github.com/webmappsrl/osmfeatures/commit/e5d4c98dbc19024ec9385dd06c3c3d73da6ad31d))

## [1.15.1](https://github.com/webmappsrl/osmfeatures/compare/v1.15.0...v1.15.1) (2024-04-12)


### Bug Fixes

* fixed wiki icons ([633a003](https://github.com/webmappsrl/osmfeatures/commit/633a003e3329a744b03d13d5210afb4bbc19b6db))

## [1.15.0](https://github.com/webmappsrl/osmfeatures/compare/v1.14.1...v1.15.0) (2024-04-12)


### Features

* added examples to api documentation ([774e634](https://github.com/webmappsrl/osmfeatures/commit/774e63413609e26beb273566a8bda274d5490640))

## [1.14.1](https://github.com/webmappsrl/osmfeatures/compare/v1.14.0...v1.14.1) (2024-04-12)


### Bug Fixes

* fixed swagger api documentation ([10be0cd](https://github.com/webmappsrl/osmfeatures/commit/10be0cd21ae30e7d84b230128d85259959dac5dd))

## [1.14.0](https://github.com/webmappsrl/osmfeatures/compare/v1.13.4...v1.14.0) (2024-04-10)


### Features

* admin areas nova list enhancement ([02f1d98](https://github.com/webmappsrl/osmfeatures/commit/02f1d98858d8272c26c99f991279a1ff757acf94))
* changed default dashboard to features ([b3ee681](https://github.com/webmappsrl/osmfeatures/commit/b3ee681451c5596b316d66af67e29abef77ff1a5))

## [1.13.4](https://github.com/webmappsrl/osmfeatures/compare/v1.13.3...v1.13.4) (2024-04-09)


### Bug Fixes

* fixed osm2cai_status computed value ([4df54c7](https://github.com/webmappsrl/osmfeatures/commit/4df54c7639c2d4b85696ed2a28c89ea81df0a74d))

## [1.13.3](https://github.com/webmappsrl/osmfeatures/compare/v1.13.2...v1.13.3) (2024-04-09)


### Bug Fixes

* pole nova imported classes ([a177a5b](https://github.com/webmappsrl/osmfeatures/commit/a177a5bc9a33480f5e6d61f9b593fd24dab098b8))

## [1.13.2](https://github.com/webmappsrl/osmfeatures/compare/v1.13.1...v1.13.2) (2024-04-09)


### Bug Fixes

* fixed errors ([22890d0](https://github.com/webmappsrl/osmfeatures/commit/22890d027203d4dc82767ead2933d813f4b80344))
* fixed errors ([35e12c6](https://github.com/webmappsrl/osmfeatures/commit/35e12c6e3c06865ccb2ffbcb2273d1073143aaaf))
* imported classes ([b5ad6a0](https://github.com/webmappsrl/osmfeatures/commit/b5ad6a08c13d6a9f975c75c6b82b94a890d2f686))

## [1.13.1](https://github.com/webmappsrl/osmfeatures/compare/v1.13.0...v1.13.1) (2024-04-09)


### Bug Fixes

* imported classes ([d243c85](https://github.com/webmappsrl/osmfeatures/commit/d243c8512926daffccc8686781d9e6d0114a8a9e))

## [1.13.0](https://github.com/webmappsrl/osmfeatures/compare/v1.12.1...v1.13.0) (2024-04-09)


### Features

* added bbox parameter to list apis ([20044b6](https://github.com/webmappsrl/osmfeatures/commit/20044b6c20d6ead89ffa0a89279c863d9c718617))
* updated swagger ([2c65c0b](https://github.com/webmappsrl/osmfeatures/commit/2c65c0be8e4db38fcafb1153d911ebb01eb30226))
* written tests for list apis ([7fe3c11](https://github.com/webmappsrl/osmfeatures/commit/7fe3c111c9d7e8a0b89aa14fa113cef51aaf1b15))


### Bug Fixes

* fix test ([4ba7a4d](https://github.com/webmappsrl/osmfeatures/commit/4ba7a4dbd62d16bedfd53326b2037d6614518c31))
* fixed errors ([716e8f9](https://github.com/webmappsrl/osmfeatures/commit/716e8f970dd7715ec6cb69cf39de213b9a0d82c9))
* fixed swagger ([5f80636](https://github.com/webmappsrl/osmfeatures/commit/5f8063602e31cc4a29d2da4ee5c32ac517b01d9e))
* fixed tags field ([ab4834d](https://github.com/webmappsrl/osmfeatures/commit/ab4834da5cc21d1f58a050c5e11609759b91f22c))
* fixed tests ([7bab415](https://github.com/webmappsrl/osmfeatures/commit/7bab415c2654a52020492f746324049cd8d70bc8))
* tests ([5d57a59](https://github.com/webmappsrl/osmfeatures/commit/5d57a59c9c54eb19e4abbb5ceaf8a01ca5af5eb2))

## [1.12.1](https://github.com/webmappsrl/osmfeatures/compare/v1.12.0...v1.12.1) (2024-04-08)

### Bug Fixes

-   fixed errors ([9a8647f](https://github.com/webmappsrl/osmfeatures/commit/9a8647f269a95188875a6af1f9796e1b6dd4e5c4))

## [1.12.0](https://github.com/webmappsrl/osmfeatures/compare/v1.11.0...v1.12.0) (2024-04-08)

### Features

-   added features dashboard ([43f510b](https://github.com/webmappsrl/osmfeatures/commit/43f510b5ffaac732e7545565f9b0e5fd172ac86c))
-   added pagination to lists api ([26c8740](https://github.com/webmappsrl/osmfeatures/commit/26c8740752af62475b436a21081f6c4c16ebaa5a))
-   admin areas list optional updated_at parameter ([d1cdeef](https://github.com/webmappsrl/osmfeatures/commit/d1cdeefb9bbb68c7a73d962835999ac751e7acf2))
-   admin areas nova list enhancement ([8627710](https://github.com/webmappsrl/osmfeatures/commit/8627710e1b5f326a5dffc054ba8f2b0725566431))
-   admin areas nova list specific enhancement ([2ee60c7](https://github.com/webmappsrl/osmfeatures/commit/2ee60c75f1f446ea1f5e95fcf193baac37022d05))
-   api documentation enhancement ([951be4e](https://github.com/webmappsrl/osmfeatures/commit/951be4e70975bb8867a28af8a9e67e5aa88440f3))
-   api general documentation enhancement ([2bfdd88](https://github.com/webmappsrl/osmfeatures/commit/2bfdd88f3543d8a4b77af1b26c2a635ba2a02e6e))
-   changed osm id to internal id for api resources ([958c3cd](https://github.com/webmappsrl/osmfeatures/commit/958c3cdbfa70a0e1106dd2233bc1094f2e803aa1))
-   hiking routes nova list enhancement ([88d6a3c](https://github.com/webmappsrl/osmfeatures/commit/88d6a3c982b14031ebcee4a63374b3528963a9da))
-   hiking routes nova list specific enhancement ([de1643b](https://github.com/webmappsrl/osmfeatures/commit/de1643bc2747dd7c9a9add121f4b6fb96df90c0c))
-   implemented api tests first version ([86218a8](https://github.com/webmappsrl/osmfeatures/commit/86218a88da0256c9156f4edb136a7c6d0cf752dc))
-   implemented elevation range filter in places nova ([d397e2b](https://github.com/webmappsrl/osmfeatures/commit/d397e2bb84f7c1468d60e078eeec3b2791341d18))
-   list optional updated at parameter implemented ([b925374](https://github.com/webmappsrl/osmfeatures/commit/b9253748d01f6675141455297d456b17628ce0f6))
-   menu enhancement ([1386589](https://github.com/webmappsrl/osmfeatures/commit/1386589d074f70b677217940162fe47d93e98aea))
-   ordered list api results starting from the most recent record ([5dedb4c](https://github.com/webmappsrl/osmfeatures/commit/5dedb4c9df7a86a14f9ba75eafb94baf07d822e8))
-   poles nova list enhancement ([f5713d8](https://github.com/webmappsrl/osmfeatures/commit/f5713d8eba12aee1bda1bd994d23e5ca2e0d314b))
-   poles nova list specific enhancement ([58b7f4e](https://github.com/webmappsrl/osmfeatures/commit/58b7f4e77e880e18e28ba88ef17582980ebc48d6))
-   readme enhancement ([f3b49ff](https://github.com/webmappsrl/osmfeatures/commit/f3b49ff7e43ecf8a579a5a6b6a096edd57e7b6c6))

### Bug Fixes

-   fixed last update card ([0c09a3e](https://github.com/webmappsrl/osmfeatures/commit/0c09a3e8cbc74e2ddcac7ce8051ef9bd8c7e8a60))
-   fixed osm_id in api documentation examples ([fe83a4f](https://github.com/webmappsrl/osmfeatures/commit/fe83a4f6af05c7a4575581a34dc919e7d24b203e))
-   fixed test workflow ([2d9db45](https://github.com/webmappsrl/osmfeatures/commit/2d9db45136257fe81372b102d48891649d3951c9))
-   fixed workflow ([7008f93](https://github.com/webmappsrl/osmfeatures/commit/7008f93890627a36cff88dbe6ae7f9513b0b25d6))
-   removed pois from api documentation ([14ed91a](https://github.com/webmappsrl/osmfeatures/commit/14ed91a1919b588d201b8f7994a5c7f5484bd8b6))
-   test workflow ([adeeea7](https://github.com/webmappsrl/osmfeatures/commit/adeeea7303433c860a237179164df3f312fff213))
-   updated deploy dev script ([4306a63](https://github.com/webmappsrl/osmfeatures/commit/4306a63f9d1173bbef6d878229574cd0ab21b1ae))
-   workflow fix ([6638bba](https://github.com/webmappsrl/osmfeatures/commit/6638bbabdc598f81dd15be0229df36894d60c34d))

## [1.11.0](https://github.com/webmappsrl/osmfeatures/compare/v1.10.2...v1.11.0) (2024-03-26)

### Features

-   added bbox parameter to list apis ([20044b6](https://github.com/webmappsrl/osmfeatures/commit/20044b6c20d6ead89ffa0a89279c863d9c718617))
-   updated swagger ([2c65c0b](https://github.com/webmappsrl/osmfeatures/commit/2c65c0be8e4db38fcafb1153d911ebb01eb30226))
-   written tests for list apis ([7fe3c11](https://github.com/webmappsrl/osmfeatures/commit/7fe3c111c9d7e8a0b89aa14fa113cef51aaf1b15))

### Bug Fixes

-   fix test ([4ba7a4d](https://github.com/webmappsrl/osmfeatures/commit/4ba7a4dbd62d16bedfd53326b2037d6614518c31))
-   fixed errors ([716e8f9](https://github.com/webmappsrl/osmfeatures/commit/716e8f970dd7715ec6cb69cf39de213b9a0d82c9))
-   fixed swagger ([5f80636](https://github.com/webmappsrl/osmfeatures/commit/5f8063602e31cc4a29d2da4ee5c32ac517b01d9e))
-   fixed tags field ([ab4834d](https://github.com/webmappsrl/osmfeatures/commit/ab4834da5cc21d1f58a050c5e11609759b91f22c))
-   fixed tests ([7bab415](https://github.com/webmappsrl/osmfeatures/commit/7bab415c2654a52020492f746324049cd8d70bc8))
-   tests ([5d57a59](https://github.com/webmappsrl/osmfeatures/commit/5d57a59c9c54eb19e4abbb5ceaf8a01ca5af5eb2))

## [1.12.1](https://github.com/webmappsrl/osmfeatures/compare/v1.12.0...v1.12.1) (2024-04-08)

### Bug Fixes

-   fixed errors ([9a8647f](https://github.com/webmappsrl/osmfeatures/commit/9a8647f269a95188875a6af1f9796e1b6dd4e5c4))

## [1.12.0](https://github.com/webmappsrl/osmfeatures/compare/v1.11.0...v1.12.0) (2024-04-08)

### Features

-   added features dashboard ([43f510b](https://github.com/webmappsrl/osmfeatures/commit/43f510b5ffaac732e7545565f9b0e5fd172ac86c))
-   added pagination to lists api ([26c8740](https://github.com/webmappsrl/osmfeatures/commit/26c8740752af62475b436a21081f6c4c16ebaa5a))
-   admin areas list optional updated_at parameter ([d1cdeef](https://github.com/webmappsrl/osmfeatures/commit/d1cdeefb9bbb68c7a73d962835999ac751e7acf2))
-   admin areas nova list enhancement ([8627710](https://github.com/webmappsrl/osmfeatures/commit/8627710e1b5f326a5dffc054ba8f2b0725566431))
-   admin areas nova list specific enhancement ([2ee60c7](https://github.com/webmappsrl/osmfeatures/commit/2ee60c75f1f446ea1f5e95fcf193baac37022d05))
-   api documentation enhancement ([951be4e](https://github.com/webmappsrl/osmfeatures/commit/951be4e70975bb8867a28af8a9e67e5aa88440f3))
-   api general documentation enhancement ([2bfdd88](https://github.com/webmappsrl/osmfeatures/commit/2bfdd88f3543d8a4b77af1b26c2a635ba2a02e6e))
-   changed osm id to internal id for api resources ([958c3cd](https://github.com/webmappsrl/osmfeatures/commit/958c3cdbfa70a0e1106dd2233bc1094f2e803aa1))
-   hiking routes nova list enhancement ([88d6a3c](https://github.com/webmappsrl/osmfeatures/commit/88d6a3c982b14031ebcee4a63374b3528963a9da))
-   hiking routes nova list specific enhancement ([de1643b](https://github.com/webmappsrl/osmfeatures/commit/de1643bc2747dd7c9a9add121f4b6fb96df90c0c))
-   implemented api tests first version ([86218a8](https://github.com/webmappsrl/osmfeatures/commit/86218a88da0256c9156f4edb136a7c6d0cf752dc))
-   implemented elevation range filter in places nova ([d397e2b](https://github.com/webmappsrl/osmfeatures/commit/d397e2bb84f7c1468d60e078eeec3b2791341d18))
-   list optional updated at parameter implemented ([b925374](https://github.com/webmappsrl/osmfeatures/commit/b9253748d01f6675141455297d456b17628ce0f6))
-   menu enhancement ([1386589](https://github.com/webmappsrl/osmfeatures/commit/1386589d074f70b677217940162fe47d93e98aea))
-   ordered list api results starting from the most recent record ([5dedb4c](https://github.com/webmappsrl/osmfeatures/commit/5dedb4c9df7a86a14f9ba75eafb94baf07d822e8))
-   poles nova list enhancement ([f5713d8](https://github.com/webmappsrl/osmfeatures/commit/f5713d8eba12aee1bda1bd994d23e5ca2e0d314b))
-   poles nova list specific enhancement ([58b7f4e](https://github.com/webmappsrl/osmfeatures/commit/58b7f4e77e880e18e28ba88ef17582980ebc48d6))
-   readme enhancement ([f3b49ff](https://github.com/webmappsrl/osmfeatures/commit/f3b49ff7e43ecf8a579a5a6b6a096edd57e7b6c6))

### Bug Fixes

-   fixed last update card ([0c09a3e](https://github.com/webmappsrl/osmfeatures/commit/0c09a3e8cbc74e2ddcac7ce8051ef9bd8c7e8a60))
-   fixed osm_id in api documentation examples ([fe83a4f](https://github.com/webmappsrl/osmfeatures/commit/fe83a4f6af05c7a4575581a34dc919e7d24b203e))
-   fixed test workflow ([2d9db45](https://github.com/webmappsrl/osmfeatures/commit/2d9db45136257fe81372b102d48891649d3951c9))
-   fixed workflow ([7008f93](https://github.com/webmappsrl/osmfeatures/commit/7008f93890627a36cff88dbe6ae7f9513b0b25d6))
-   removed pois from api documentation ([14ed91a](https://github.com/webmappsrl/osmfeatures/commit/14ed91a1919b588d201b8f7994a5c7f5484bd8b6))
-   test workflow ([adeeea7](https://github.com/webmappsrl/osmfeatures/commit/adeeea7303433c860a237179164df3f312fff213))
-   updated deploy dev script ([4306a63](https://github.com/webmappsrl/osmfeatures/commit/4306a63f9d1173bbef6d878229574cd0ab21b1ae))
-   workflow fix ([6638bba](https://github.com/webmappsrl/osmfeatures/commit/6638bbabdc598f81dd15be0229df36894d60c34d))

## [1.11.0](https://github.com/webmappsrl/osmfeatures/compare/v1.10.2...v1.11.0) (2024-03-26)

### Features

-   implemented api for hiking routes and places ([a57f505](https://github.com/webmappsrl/osmfeatures/commit/a57f5052babeb4d2e30f54255b92638c5d323d3d))
-   implemented correct timestamp compute for hiking routes ([e1803bc](https://github.com/webmappsrl/osmfeatures/commit/e1803bc8abb5ff488f2b5ecab378560158611f37))
-   implemented osm2pgsql update pbf command 1st version ([30cc940](https://github.com/webmappsrl/osmfeatures/commit/30cc94017e4bc6e2e360c62e08aa863b77af78f1))
-   implemented update command for italy pbf ([01cd9ce](https://github.com/webmappsrl/osmfeatures/commit/01cd9cec2a434bb295cebe42c25434cb7363cd97))
-   updated readme ([7f4895d](https://github.com/webmappsrl/osmfeatures/commit/7f4895de0e26b5715b5d4f3c32681dc40c8209b7))

### Bug Fixes

-   fixed hiking_routes lua file to import relation members ([d1f652b](https://github.com/webmappsrl/osmfeatures/commit/d1f652b9f8defc92b2a4adca7b0f93313dc6877b))

## [1.10.2](https://github.com/webmappsrl/osmfeatures/compare/v1.10.1...v1.10.2) (2024-03-25)

### Bug Fixes

-   fixed osm id link for node way and relations ([c0909f6](https://github.com/webmappsrl/osmfeatures/commit/c0909f6ed275147d968a0c1c888c070e3488fee8))

## [1.10.1](https://github.com/webmappsrl/osmfeatures/compare/v1.10.0...v1.10.1) (2024-03-23)

### Bug Fixes

-   error on hiking ways ([88e0e5b](https://github.com/webmappsrl/osmfeatures/commit/88e0e5b4bdc98751df32f9759a9077027d68c95f))

## [1.10.0](https://github.com/webmappsrl/osmfeatures/compare/v1.9.0...v1.10.0) (2024-03-23)

### Features

-   added filter for classes in places nova ([d6c4bf8](https://github.com/webmappsrl/osmfeatures/commit/d6c4bf833849db0ce428d816afa574cddeaf507c))
-   added hiking routes model and nova resource ([acfdc0c](https://github.com/webmappsrl/osmfeatures/commit/acfdc0c9ca745d64ee987182d28a273f69a69cba))
-   added osm type filters for nova resources ([101ebe0](https://github.com/webmappsrl/osmfeatures/commit/101ebe07f7a04bbea3353da3e89acc157f2ca3cd))
-   added places model and nova resource ([e103028](https://github.com/webmappsrl/osmfeatures/commit/e103028913811a9f62d38ee17d5c025002746684))
-   added tooltip to tags in nova resources ([3574d90](https://github.com/webmappsrl/osmfeatures/commit/3574d90917f2ea4af069f1e84b88f5b6f88d786d))
-   created hiking routes lua file ([44538dd](https://github.com/webmappsrl/osmfeatures/commit/44538ddafd47be98169bcf232988c849b973dd84))
-   created places lua file ([cc5cb95](https://github.com/webmappsrl/osmfeatures/commit/cc5cb95b62d046e2fa942a3f2df78bf014ea7365))

### Bug Fixes

-   fixed osm type in poi, pole, admin area nova resources ([494a03d](https://github.com/webmappsrl/osmfeatures/commit/494a03dba23940a71070fd1cff055210559bb3e6))
-   fixed places lua file to import relations ([c612b89](https://github.com/webmappsrl/osmfeatures/commit/c612b896806fdec35519ab3f31451e5947f53f80))
-   fixed updated_at and tags ([f3c9922](https://github.com/webmappsrl/osmfeatures/commit/f3c9922cc7e09b657b257174b6f0c77a1fbd584d))

## [1.9.0](https://github.com/webmappsrl/osmfeatures/compare/v1.8.0...v1.9.0) (2024-03-05)

### Features

-   improved sync command and added updated_at in nova index ([f857fbb](https://github.com/webmappsrl/osmfeatures/commit/f857fbb759a98d55928232d6823c80953d399189))

### Bug Fixes

-   fixed typo in sync command ([365a319](https://github.com/webmappsrl/osmfeatures/commit/365a319a1f5e0fd879c52f0236173b52e67465d3))
-   fixed typo in sync command ([5d200fa](https://github.com/webmappsrl/osmfeatures/commit/5d200fad5628243ce5a1f0468eae411a95a04802))

## [1.8.0](https://github.com/webmappsrl/osmfeatures/compare/v1.7.0...v1.8.0) (2024-03-05)

### Features

-   added filters for wikis in nova resources admin areas poles and pois ([c7a76f0](https://github.com/webmappsrl/osmfeatures/commit/c7a76f0b6d7e48ebf7d071c17d30df40517e3c9b))
-   added wiki field in nova for admin areas poles and poi resources ([4085397](https://github.com/webmappsrl/osmfeatures/commit/40853970706e41fcf0885105560eaeef875224ba))
-   added wikimedia field for pois poles and admin areas nova ([66b6fc6](https://github.com/webmappsrl/osmfeatures/commit/66b6fc6d9d3e84ceda4a28930b1fa374f2075da9))
-   created trait for processing osm tags data ([9e33d86](https://github.com/webmappsrl/osmfeatures/commit/9e33d8604cd12991beaac042c579062b54db1880))
-   implemented wikidata filter ([bf3a80f](https://github.com/webmappsrl/osmfeatures/commit/bf3a80f9b000d8cda097f369de3b0e347c85c178))
-   updated readme and uploaded screenshot ([515855a](https://github.com/webmappsrl/osmfeatures/commit/515855aab58e6aaaa61fee5323d5222ac4f506a7))

### Bug Fixes

-   deactivated migrate:fresh in deploy dev ([45b6b65](https://github.com/webmappsrl/osmfeatures/commit/45b6b653275c71fdadb7715b848bb02ab01533d5))
-   fixed admin user seeder ([bb76bd8](https://github.com/webmappsrl/osmfeatures/commit/bb76bd8d2abdbe240422e9980fce58db7173aada))
-   fixed date to iso format in APIs ([26458e0](https://github.com/webmappsrl/osmfeatures/commit/26458e08afd6d03045af8c1c874aba81081c50c7))
-   fixed db host for osm2pgsql command (to test in develop and github actions) ([f790814](https://github.com/webmappsrl/osmfeatures/commit/f7908147973cf68396359970a08348452d2b37aa))
-   fixed updated_at in pbf import ([03478ba](https://github.com/webmappsrl/osmfeatures/commit/03478bab939a7cfb10b86a7509c91cecea1e1f54))
-   temporarily deactivated automatic sync on dev deploy (db host issue) ([1b13dfd](https://github.com/webmappsrl/osmfeatures/commit/1b13dfd05cd59344bfdcf63772afc1f1729134f9))

## [1.7.0](https://github.com/webmappsrl/osmfeatures/compare/v1.6.0...v1.7.0) (2024-01-31)

### Features

-   added osm2pgsql import for all the lua files in deploy dev script ([b771cd9](https://github.com/webmappsrl/osmfeatures/commit/b771cd90ed6cabefff5bb5dd7bf3abf79a439368))
-   updated documentation ([c47fcc7](https://github.com/webmappsrl/osmfeatures/commit/c47fcc70670ebed19996e9e61942466b3d441397))

### Bug Fixes

-   added skip download to workflow ([77fc843](https://github.com/webmappsrl/osmfeatures/commit/77fc8433ac47913c2d0ad586aa650f7e01f0b93d))
-   dev-deploy ([1c33f99](https://github.com/webmappsrl/osmfeatures/commit/1c33f99f1f8ce758531b43457d40183ffea748c7))
-   skip download on deploy dev sync ([1e0ea9d](https://github.com/webmappsrl/osmfeatures/commit/1e0ea9d2e95742a1e5d1e52b8aedee29fa4c89ed))

## [1.6.0](https://github.com/webmappsrl/osmfeatures/compare/v1.5.1...v1.6.0) (2024-01-30)

### Features

-   activated automatic sync in deploy dev workflow ([a6d8225](https://github.com/webmappsrl/osmfeatures/commit/a6d82252b33a11cc9d14ef2675cd278a2be5dd39))

## [1.5.1](https://github.com/webmappsrl/osmfeatures/compare/v1.5.0...v1.5.1) (2024-01-30)

### Bug Fixes

-   changed default name for automatic download on deploy dev ([ab37c53](https://github.com/webmappsrl/osmfeatures/commit/ab37c539dae8eeb8a0b36f689c1dea0a3825596e))

## [1.5.0](https://github.com/webmappsrl/osmfeatures/compare/v1.4.0...v1.5.0) (2024-01-29)

### Features

-   added admin permission for prd ([0bd76ab](https://github.com/webmappsrl/osmfeatures/commit/0bd76abc5573e27a3543c8599326a0a5f1c8a536))

### Bug Fixes

-   fixed url for geofabrik download default ([a272078](https://github.com/webmappsrl/osmfeatures/commit/a2720783f1b4dc42880b9f8072fce514ca55a96a))

## [1.3.2](https://github.com/webmappsrl/osmfeatures/compare/v1.3.1...v1.3.2) (2024-01-27)

### Bug Fixes

-   change command to use env password and input database host ([5f1bbca](https://github.com/webmappsrl/osmfeatures/commit/5f1bbca6100371ae89d639283398f505eb88a881))
-   fixed typo in sync command ([692cd5c](https://github.com/webmappsrl/osmfeatures/commit/692cd5c251ba34c1eb43f1867c20523dd816ef1c))

## [1.3.1](https://github.com/webmappsrl/osmfeatures/compare/v1.3.0...v1.3.1) (2024-01-24)

=======

## [1.4.0](https://github.com/webmappsrl/osmfeatures/compare/v1.3.2...v1.4.0) (2024-01-29)

### Features

-   added tags field to pois sync ([102059a](https://github.com/webmappsrl/osmfeatures/commit/102059a94bbcef358b56cf12c504cc6779cef737))
-   deploy dev optimized to launch osm2pgsql sync for montepisano ([321a828](https://github.com/webmappsrl/osmfeatures/commit/321a8284c6d059ff92f87eb345a1b143ed027d1b))
-   enhanced API documentation ([8614d40](https://github.com/webmappsrl/osmfeatures/commit/8614d408c6c3f78360a68aa8b1b85febc452e912))
-   enhanced sync command with laravel prompts and created import for poles, model, nova and APIs ([43786f6](https://github.com/webmappsrl/osmfeatures/commit/43786f62b4c6bcbd2e0106998743a96e97895de6))
-   sync admin areas, nova resource, model, apis ([901046f](https://github.com/webmappsrl/osmfeatures/commit/901046f8c3f68cc446e0918a3cdef48d625ffb6c))
-   updated pois.lua to import only certain subclasses ([ee7db9c](https://github.com/webmappsrl/osmfeatures/commit/ee7db9c3b50681506d968ae3698ecd180ab59cfc))

### Bug Fixes

-   clean_code ([d295bc5](https://github.com/webmappsrl/osmfeatures/commit/d295bc59f52bd1f441f1fb8b36cdc63598278ea9))

## [1.3.2](https://github.com/webmappsrl/osmfeatures/compare/v1.3.1...v1.3.2) (2024-01-27)

### Bug Fixes

-   change command to use env password and input database host ([5f1bbca](https://github.com/webmappsrl/osmfeatures/commit/5f1bbca6100371ae89d639283398f505eb88a881))
-   fixed typo in sync command ([692cd5c](https://github.com/webmappsrl/osmfeatures/commit/692cd5c251ba34c1eb43f1867c20523dd816ef1c))

## [1.3.1](https://github.com/webmappsrl/osmfeatures/compare/v1.3.0...v1.3.1) (2024-01-24)

### Bug Fixes

-   fixed path in sync ([c01e798](https://github.com/webmappsrl/osmfeatures/commit/c01e798f8127b95eff2caede8c30c410ff8875ee))

## [1.3.0](https://github.com/webmappsrl/osmfeatures/compare/v1.2.0...v1.3.0) (2024-01-24)

### Features

-   added footer to nova ([ea39531](https://github.com/webmappsrl/osmfeatures/commit/ea3953142af33202c55d3a55e4be8e872cda26a1))
-   added footer to nova ([bd171db](https://github.com/webmappsrl/osmfeatures/commit/bd171db70aa274e7bfcdb5a09bcd753886d012d3))
-   added sync command with osmium and osm2pgsql ([d1dfcb2](https://github.com/webmappsrl/osmfeatures/commit/d1dfcb2bbfe43a9a00520b4cd0af9e965fb5bd10))
-   added sync command with osmium and osm2pgsql ([efe0097](https://github.com/webmappsrl/osmfeatures/commit/efe009798cc76a702c0bb8c05d0a42117cd5a652))
-   implemented nova resource for pois ([fcef6da](https://github.com/webmappsrl/osmfeatures/commit/fcef6dac060eea4c31581aeaab71974ee6db9c23))
-   implemented nova resource for pois ([79368ae](https://github.com/webmappsrl/osmfeatures/commit/79368ae7a90f38fff716afa6ae778c1c507fd54d))
-   implemented poi list api ([7c3ee11](https://github.com/webmappsrl/osmfeatures/commit/7c3ee11f7db1342c5a0424b3d745bee7f8fb6d94))
-   implemented poi list api ([ceea5a6](https://github.com/webmappsrl/osmfeatures/commit/ceea5a6cdd78f8ce92877204e860cfda51c1c998))
-   swagger 1st version ([cdb6598](https://github.com/webmappsrl/osmfeatures/commit/cdb6598af0b9fccc464abebd4c356d875f1af042))
-   uploaded lua file ([767bc16](https://github.com/webmappsrl/osmfeatures/commit/767bc165758e7dd016f6d624365b854daeedcb83))

## [1.2.0](https://github.com/webmappsrl/osmfeatures/compare/v1.1.2...v1.2.0) (2024-01-24)

### Features

-   added footer to nova ([0423a70](https://github.com/webmappsrl/osmfeatures/commit/0423a702312d95679d175201c78825fa07922583))
-   added osmium and osm2pgsql libraries to docker ([3c86ca4](https://github.com/webmappsrl/osmfeatures/commit/3c86ca4f15fc5f32e7cc6f2911b568e81fb5a135))
-   added php cs fixer config file ([11d8604](https://github.com/webmappsrl/osmfeatures/commit/11d8604be6b79ea441f60f883a9d62dce43bdaf2))
-   added sync command with osmium and osm2pgsql ([b93f4ea](https://github.com/webmappsrl/osmfeatures/commit/b93f4eaca413a529fa14b278ea4a2072a4c20bbe))
-   implemented nova resource for pois ([1c46e11](https://github.com/webmappsrl/osmfeatures/commit/1c46e1129b8540266298d2e1bf9be0acb99eadc3))
-   implemented poi list api ([d56ed4b](https://github.com/webmappsrl/osmfeatures/commit/d56ed4b84f6b77052e3298e81cb2ebf2b7b1be15))
-   implemented poi/id api ([ddfcb92](https://github.com/webmappsrl/osmfeatures/commit/ddfcb92ad75ba7576c1f061e880f9578295cf5ba))
-   swagger 1st version ([593cc25](https://github.com/webmappsrl/osmfeatures/commit/593cc2572ee255c8720748aa4e58b2457940f129))
-   uploaded lua file ([3ae48b3](https://github.com/webmappsrl/osmfeatures/commit/3ae48b3025d568f1b09e005f690cbde5d1cf75df))

### Bug Fixes

-   fixed deploy_dev script ([c54086b](https://github.com/webmappsrl/osmfeatures/commit/c54086b3b35f81bdcf76fa529cd10572a215486b))
-   fixed xdebug conf ([da29f52](https://github.com/webmappsrl/osmfeatures/commit/da29f522f97d0dbac38339f5a2e1cf58cf60804a))

## [1.1.2](https://github.com/webmappsrl/osmfeatures/compare/v1.1.1...v1.1.2) (2024-01-23)

### Bug Fixes

-   fixed feature example test ([39881c5](https://github.com/webmappsrl/osmfeatures/commit/39881c556260bddfe8f38518724bb52c64822e6e))

## [1.1.1](https://github.com/webmappsrl/osmfeatures/compare/v1.1.0...v1.1.1) (2024-01-23)

-   added footer to nova ([0423a70](https://github.com/webmappsrl/osmfeatures/commit/0423a702312d95679d175201c78825fa07922583))
-   added osmium and osm2pgsql libraries to docker ([3c86ca4](https://github.com/webmappsrl/osmfeatures/commit/3c86ca4f15fc5f32e7cc6f2911b568e81fb5a135))
-   added php cs fixer config file ([11d8604](https://github.com/webmappsrl/osmfeatures/commit/11d8604be6b79ea441f60f883a9d62dce43bdaf2))
-   added sync command with osmium and osm2pgsql ([b93f4ea](https://github.com/webmappsrl/osmfeatures/commit/b93f4eaca413a529fa14b278ea4a2072a4c20bbe))
-   implemented nova resource for pois ([1c46e11](https://github.com/webmappsrl/osmfeatures/commit/1c46e1129b8540266298d2e1bf9be0acb99eadc3))
-   implemented poi list api ([d56ed4b](https://github.com/webmappsrl/osmfeatures/commit/d56ed4b84f6b77052e3298e81cb2ebf2b7b1be15))
-   implemented poi/id api ([ddfcb92](https://github.com/webmappsrl/osmfeatures/commit/ddfcb92ad75ba7576c1f061e880f9578295cf5ba))
-   swagger 1st version ([593cc25](https://github.com/webmappsrl/osmfeatures/commit/593cc2572ee255c8720748aa4e58b2457940f129))
-   uploaded lua file ([3ae48b3](https://github.com/webmappsrl/osmfeatures/commit/3ae48b3025d568f1b09e005f690cbde5d1cf75df))

### Bug Fixes

-   fixed deploy_dev script ([c54086b](https://github.com/webmappsrl/osmfeatures/commit/c54086b3b35f81bdcf76fa529cd10572a215486b))
-   fixed xdebug conf ([da29f52](https://github.com/webmappsrl/osmfeatures/commit/da29f522f97d0dbac38339f5a2e1cf58cf60804a))

## [1.1.2](https://github.com/webmappsrl/osmfeatures/compare/v1.1.1...v1.1.2) (2024-01-23)

### Bug Fixes

-   fixed feature example test ([39881c5](https://github.com/webmappsrl/osmfeatures/commit/39881c556260bddfe8f38518724bb52c64822e6e))

## [1.1.1](https://github.com/webmappsrl/osmfeatures/compare/v1.1.0...v1.1.1) (2024-01-23)

### Bug Fixes

-   fixed homepage path to nova login ([ebb7b49](https://github.com/webmappsrl/osmfeatures/commit/ebb7b499491f42b37f4f183364c05de60f979218))

## [1.1.0](https://github.com/webmappsrl/osmfeatures/compare/v1.0.0...v1.1.0) (2024-01-23)

### Features

-   updated readme and route ([db0fad1](https://github.com/webmappsrl/osmfeatures/commit/db0fad1ac7275d4f7ea73b89acd84be006518ffc))

## 1.0.0 (2024-01-23)

### Features

-   added nova login as default home page and created admin user seeder ([e679d4e](https://github.com/webmappsrl/osmfeatures/commit/e679d4eda661206e610bec0270940696e145b0d2))
-   added nova login as default home page and created admin user seeder ([8c36835](https://github.com/webmappsrl/osmfeatures/commit/8c368359786251756dce68af05585de6eb5d738b))
-   aws configuration ([d7e8693](https://github.com/webmappsrl/osmfeatures/commit/d7e86934682d4a3a832704291bba3bae7c2a2a95))
-   updated docker images and dependencies ([b410e1d](https://github.com/webmappsrl/osmfeatures/commit/b410e1d41206cc5f6c5bfe0768df8751e3fc58d5))
-   updated docker images and dependencies ([edaed59](https://github.com/webmappsrl/osmfeatures/commit/edaed593f2e3a5d8152f50ba823c7e1a6c1c5f54))
