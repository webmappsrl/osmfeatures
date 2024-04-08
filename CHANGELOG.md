# Changelog

## [1.11.0](https://github.com/webmappsrl/osmfeatures/compare/v1.10.2...v1.11.0) (2024-03-26)


### Features

* implemented api for hiking routes and places ([a57f505](https://github.com/webmappsrl/osmfeatures/commit/a57f5052babeb4d2e30f54255b92638c5d323d3d))
* implemented correct timestamp compute for hiking routes ([e1803bc](https://github.com/webmappsrl/osmfeatures/commit/e1803bc8abb5ff488f2b5ecab378560158611f37))
* implemented osm2pgsql update pbf command 1st version ([30cc940](https://github.com/webmappsrl/osmfeatures/commit/30cc94017e4bc6e2e360c62e08aa863b77af78f1))
* implemented update command for italy pbf ([01cd9ce](https://github.com/webmappsrl/osmfeatures/commit/01cd9cec2a434bb295cebe42c25434cb7363cd97))
* updated readme ([7f4895d](https://github.com/webmappsrl/osmfeatures/commit/7f4895de0e26b5715b5d4f3c32681dc40c8209b7))


### Bug Fixes

* fixed hiking_routes lua file to import relation members ([d1f652b](https://github.com/webmappsrl/osmfeatures/commit/d1f652b9f8defc92b2a4adca7b0f93313dc6877b))

## [1.10.2](https://github.com/webmappsrl/osmfeatures/compare/v1.10.1...v1.10.2) (2024-03-25)


### Bug Fixes

* fixed osm id link for node way and relations ([c0909f6](https://github.com/webmappsrl/osmfeatures/commit/c0909f6ed275147d968a0c1c888c070e3488fee8))

## [1.10.1](https://github.com/webmappsrl/osmfeatures/compare/v1.10.0...v1.10.1) (2024-03-23)


### Bug Fixes

* error on hiking ways ([88e0e5b](https://github.com/webmappsrl/osmfeatures/commit/88e0e5b4bdc98751df32f9759a9077027d68c95f))

## [1.10.0](https://github.com/webmappsrl/osmfeatures/compare/v1.9.0...v1.10.0) (2024-03-23)


### Features

* added filter for classes in places nova ([d6c4bf8](https://github.com/webmappsrl/osmfeatures/commit/d6c4bf833849db0ce428d816afa574cddeaf507c))
* added hiking routes model and nova resource ([acfdc0c](https://github.com/webmappsrl/osmfeatures/commit/acfdc0c9ca745d64ee987182d28a273f69a69cba))
* added osm type filters for nova resources ([101ebe0](https://github.com/webmappsrl/osmfeatures/commit/101ebe07f7a04bbea3353da3e89acc157f2ca3cd))
* added places model and nova resource ([e103028](https://github.com/webmappsrl/osmfeatures/commit/e103028913811a9f62d38ee17d5c025002746684))
* added tooltip to tags in nova resources ([3574d90](https://github.com/webmappsrl/osmfeatures/commit/3574d90917f2ea4af069f1e84b88f5b6f88d786d))
* created hiking routes lua file ([44538dd](https://github.com/webmappsrl/osmfeatures/commit/44538ddafd47be98169bcf232988c849b973dd84))
* created places lua file ([cc5cb95](https://github.com/webmappsrl/osmfeatures/commit/cc5cb95b62d046e2fa942a3f2df78bf014ea7365))


### Bug Fixes

* fixed osm type in poi, pole, admin area nova resources ([494a03d](https://github.com/webmappsrl/osmfeatures/commit/494a03dba23940a71070fd1cff055210559bb3e6))
* fixed places lua file to import relations ([c612b89](https://github.com/webmappsrl/osmfeatures/commit/c612b896806fdec35519ab3f31451e5947f53f80))
* fixed updated_at and tags ([f3c9922](https://github.com/webmappsrl/osmfeatures/commit/f3c9922cc7e09b657b257174b6f0c77a1fbd584d))

## [1.9.0](https://github.com/webmappsrl/osmfeatures/compare/v1.8.0...v1.9.0) (2024-03-05)


### Features

* improved sync command and added updated_at in nova index ([f857fbb](https://github.com/webmappsrl/osmfeatures/commit/f857fbb759a98d55928232d6823c80953d399189))


### Bug Fixes

* fixed typo in sync command ([365a319](https://github.com/webmappsrl/osmfeatures/commit/365a319a1f5e0fd879c52f0236173b52e67465d3))
* fixed typo in sync command ([5d200fa](https://github.com/webmappsrl/osmfeatures/commit/5d200fad5628243ce5a1f0468eae411a95a04802))

## [1.8.0](https://github.com/webmappsrl/osmfeatures/compare/v1.7.0...v1.8.0) (2024-03-05)


### Features

* added filters for wikis in nova resources admin areas poles and pois ([c7a76f0](https://github.com/webmappsrl/osmfeatures/commit/c7a76f0b6d7e48ebf7d071c17d30df40517e3c9b))
* added wiki field in nova for admin areas poles and poi resources ([4085397](https://github.com/webmappsrl/osmfeatures/commit/40853970706e41fcf0885105560eaeef875224ba))
* added wikimedia field for pois poles and admin areas nova ([66b6fc6](https://github.com/webmappsrl/osmfeatures/commit/66b6fc6d9d3e84ceda4a28930b1fa374f2075da9))
* created trait for processing osm tags data ([9e33d86](https://github.com/webmappsrl/osmfeatures/commit/9e33d8604cd12991beaac042c579062b54db1880))
* implemented wikidata filter ([bf3a80f](https://github.com/webmappsrl/osmfeatures/commit/bf3a80f9b000d8cda097f369de3b0e347c85c178))
* updated readme and uploaded screenshot ([515855a](https://github.com/webmappsrl/osmfeatures/commit/515855aab58e6aaaa61fee5323d5222ac4f506a7))


### Bug Fixes

* deactivated migrate:fresh in deploy dev ([45b6b65](https://github.com/webmappsrl/osmfeatures/commit/45b6b653275c71fdadb7715b848bb02ab01533d5))
* fixed admin user seeder ([bb76bd8](https://github.com/webmappsrl/osmfeatures/commit/bb76bd8d2abdbe240422e9980fce58db7173aada))
* fixed date to iso format in APIs ([26458e0](https://github.com/webmappsrl/osmfeatures/commit/26458e08afd6d03045af8c1c874aba81081c50c7))
* fixed db host for osm2pgsql command (to test in develop and github actions) ([f790814](https://github.com/webmappsrl/osmfeatures/commit/f7908147973cf68396359970a08348452d2b37aa))
* fixed updated_at in pbf import ([03478ba](https://github.com/webmappsrl/osmfeatures/commit/03478bab939a7cfb10b86a7509c91cecea1e1f54))
* temporarily deactivated automatic sync on dev deploy (db host issue) ([1b13dfd](https://github.com/webmappsrl/osmfeatures/commit/1b13dfd05cd59344bfdcf63772afc1f1729134f9))

## [1.7.0](https://github.com/webmappsrl/osmfeatures/compare/v1.6.0...v1.7.0) (2024-01-31)


### Features

* added osm2pgsql import for all the lua files in deploy dev script ([b771cd9](https://github.com/webmappsrl/osmfeatures/commit/b771cd90ed6cabefff5bb5dd7bf3abf79a439368))
* updated documentation ([c47fcc7](https://github.com/webmappsrl/osmfeatures/commit/c47fcc70670ebed19996e9e61942466b3d441397))


### Bug Fixes

* added skip download to workflow ([77fc843](https://github.com/webmappsrl/osmfeatures/commit/77fc8433ac47913c2d0ad586aa650f7e01f0b93d))
* dev-deploy ([1c33f99](https://github.com/webmappsrl/osmfeatures/commit/1c33f99f1f8ce758531b43457d40183ffea748c7))
* skip download on deploy dev sync ([1e0ea9d](https://github.com/webmappsrl/osmfeatures/commit/1e0ea9d2e95742a1e5d1e52b8aedee29fa4c89ed))

## [1.6.0](https://github.com/webmappsrl/osmfeatures/compare/v1.5.1...v1.6.0) (2024-01-30)


### Features

* activated automatic sync in deploy dev workflow ([a6d8225](https://github.com/webmappsrl/osmfeatures/commit/a6d82252b33a11cc9d14ef2675cd278a2be5dd39))

## [1.5.1](https://github.com/webmappsrl/osmfeatures/compare/v1.5.0...v1.5.1) (2024-01-30)


### Bug Fixes

* changed default name for automatic download on deploy dev ([ab37c53](https://github.com/webmappsrl/osmfeatures/commit/ab37c539dae8eeb8a0b36f689c1dea0a3825596e))

## [1.5.0](https://github.com/webmappsrl/osmfeatures/compare/v1.4.0...v1.5.0) (2024-01-29)


### Features

* added admin permission for prd ([0bd76ab](https://github.com/webmappsrl/osmfeatures/commit/0bd76abc5573e27a3543c8599326a0a5f1c8a536))


### Bug Fixes

* fixed url for geofabrik download default ([a272078](https://github.com/webmappsrl/osmfeatures/commit/a2720783f1b4dc42880b9f8072fce514ca55a96a))

## [1.3.2](https://github.com/webmappsrl/osmfeatures/compare/v1.3.1...v1.3.2) (2024-01-27)


### Bug Fixes

* change command to use env password and input database host ([5f1bbca](https://github.com/webmappsrl/osmfeatures/commit/5f1bbca6100371ae89d639283398f505eb88a881))
* fixed typo in sync command ([692cd5c](https://github.com/webmappsrl/osmfeatures/commit/692cd5c251ba34c1eb43f1867c20523dd816ef1c))

## [1.3.1](https://github.com/webmappsrl/osmfeatures/compare/v1.3.0...v1.3.1) (2024-01-24)
=======
## [1.4.0](https://github.com/webmappsrl/osmfeatures/compare/v1.3.2...v1.4.0) (2024-01-29)


### Features

* added tags field to pois sync ([102059a](https://github.com/webmappsrl/osmfeatures/commit/102059a94bbcef358b56cf12c504cc6779cef737))
* deploy dev optimized to launch osm2pgsql sync for montepisano ([321a828](https://github.com/webmappsrl/osmfeatures/commit/321a8284c6d059ff92f87eb345a1b143ed027d1b))
* enhanced API documentation ([8614d40](https://github.com/webmappsrl/osmfeatures/commit/8614d408c6c3f78360a68aa8b1b85febc452e912))
* enhanced sync command with laravel prompts and created import for poles, model, nova and APIs ([43786f6](https://github.com/webmappsrl/osmfeatures/commit/43786f62b4c6bcbd2e0106998743a96e97895de6))
* sync admin areas, nova resource, model, apis ([901046f](https://github.com/webmappsrl/osmfeatures/commit/901046f8c3f68cc446e0918a3cdef48d625ffb6c))
* updated pois.lua to import only certain subclasses ([ee7db9c](https://github.com/webmappsrl/osmfeatures/commit/ee7db9c3b50681506d968ae3698ecd180ab59cfc))



### Bug Fixes



* clean_code ([d295bc5](https://github.com/webmappsrl/osmfeatures/commit/d295bc59f52bd1f441f1fb8b36cdc63598278ea9))

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
