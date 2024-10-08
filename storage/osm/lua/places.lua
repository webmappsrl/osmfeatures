local places = osm2pgsql.define_table({
    name = 'places',
    ids = { type = 'any', type_column = 'osm_type', id_column = 'osm_id' },
    columns = {
        { column = 'id', sql_type = 'serial', create_only = true},
        { column = 'updated_at'},
        { column = 'name' },
        { column = 'class', not_null = true },
        { column = 'subclass' },
        { column = 'geom', type = 'point', not_null = true, projection = 4326 },
        { column = 'tags', type = 'jsonb' },
        { column = 'elevation', type = 'int' },
        { column = 'score', type = 'int'}
}})



function process_place(object, geom)

    local score = 0
    -- mapping of OSM tags to our feature internal representation
       local mappings= {
            { key = 'landuse', values = { cemetery = 'cemetery'}, class = 'landuse' },
            { key = 'water', values = { lake = 'lake', pond = 'pond', lagoon = 'lagoon', basin = 'basin', reservoir = 'reservoir'}, class = 'water' },
            { key = 'waterway', values = { waterfall = 'waterfall'}, class = 'waterway' },
            { key = 'building', values = { railway_station = 'station', castle = 'castle', monastery = 'monastery', ruins = 'ruins', tower = 'tower', museum = 'museum', church = 'church', chapel = 'chapel', }, class = 'building'},
            { key = 'man_made', values = { tower = 'tower', watermill = 'watermill'}, class = 'man_made'},
            { key = 'aerialway_station', values = { station = 'station'}, class = 'aerialway'},
            { key = 'place', values = { city = 'city', town = 'town', suburb = 'suburb', suburb = 'borough', suburb = 'quarter', suburb = 'neighbourhood', suburb = 'allotments', village = 'village', square = 'square', island = 'island', islet = 'islet', hamlet = 'hamlet', isolated_dwelling = 'isolated_dwelling', isolated_dwelling = 'farm', locality = 'locality',  }, class = 'place' },
            { key = 'natural', values = { peak = 'peak', saddle = 'saddle', cape = 'cape', beach = 'beach', spring = 'spring', glacier = 'glacier', cave_entrance = 'cave_entrance',
            wood = 'wood', tree = 'tree', water = 'water', hot_spring = 'hot_spring', sinkhole = 'sinkhole', cliff = 'cliff', rock = 'rock', volcano = 'volcano',  }, class = 'natural' },
            { key = 'tourism', values = { alpine_hut = 'alpine_hut', wilderness_hut = 'wilderness_hut', aquarium = 'aquarium', camp_site = 'camp_site', caravan_site = 'caravan_site', picnic_site = 'picnic_site', hostel = 'hostel', museum = 'museum', zoo = 'zoo', theme_park = 'theme_park', artwork = 'artwork' }, class = 'tourism' },
            { key = 'historic', values = { wayside_shrine = 'wayside_shrine', wayside_cross = 'wayside_cross', monastery = 'monastery', archaeological_site = 'archaeological_site', castle = 'castle', farm = 'farm', fort = 'fort', manor = 'manor', tower = 'tower', city_gate = 'city_gate', church = 'church'}, class = 'historic' },
            { key = 'amenity', values = { place_of_worship = 'place_of_worship', cemetery = 'grave_yard', bus_station = 'bus_station', parking_point = 'parking', drinking_water = 'drinking_water', 
            hospital = 'hospital', theatre = 'theatre', university = 'university', public_building = 'public_building', 
            planetarium = 'planetarium', rock_shelter = 'rock_shelter', lavoir = 'lavoir', social_facility = 'social_facility', community_centre = 'community_centre',
            neviera = 'neviera',watering_place = 'watering_place', shelter = 'shelter', public_bath = 'public_bath', water_point = 'water_point', fountain = 'fountain',
        }, class = 'amenity' },
    }

        -- calculate score value --
    if object.tags.name then
        score = 1
    end
    if object.tags.elem then
        score = 2
    end
    if object.tags.wikidata then
        score = 3
    end
    if object.tags.wikimedia_commons then
        score = 4
    end
    if object.tags.wikipedia then
        score = 5
    end
    if object.tags['contact:website'] or object.tags.source or object.tags.website then
        score = 6
    end


    local a = {
        updated_at = os.date('%Y-%m-%d %H:%M:%S', object.timestamp) or nil,
        name = object.tags.name,
        geom = geom,
        tags = object.tags,
        elevation = object.tags.ele or nil,
        class = '',
        subclass = '',
        score = score,
    }

    for _, mapping in ipairs(mappings) do
        if object.tags[mapping.key] then
            local value = object.tags[mapping.key]
            if mapping.values[value] then
                a.class = mapping.class
                a.subclass = mapping.values[value]
                break
            end
        end
    end

    -- insert into database
    if a.class ~= '' and a.subclass ~= '' then
        places:insert(a)
    end
end

function osm2pgsql.process_node(object)
    process_place(object, object:as_point())
end

function osm2pgsql.process_way(object)
    if object.is_closed then
        process_place(object, object:as_polygon():centroid())
    else
        process_place(object, object:as_linestring():centroid())
    end
end

function osm2pgsql.process_relation(object)
    if object.tags.type == 'multipolygon' then
        local multipolygon = object:as_multipolygon()
        local centroid = multipolygon:centroid()
        process_place(object, centroid)
    end
end


