local admin_areas = osm2pgsql.define_table({
    name = 'admin_areas',
    ids = { type = 'any', type_column = 'osm_type', id_column = 'osm_id' },
    columns = {
        { column = 'id', sql_type = 'serial', create_only = true},
        { column = 'updated_at' },
        { column = 'name' },
        { column = 'tags', type = 'jsonb' },
        { column = 'geom', type = 'multipolygon' },
        { column = 'admin_level', type = 'int' },
        { column = 'score', type = 'int', default = 0 },
    }
})

local hiking_routes_ways = osm2pgsql.define_table({
    name = 'hiking_routes_ways',
    schema = 'public',
    ids = { type = 'any', type_column = 'osm_type', id_column = 'osm_id' },
    columns = {
        { column = 'updated_at'},
        { column = 'trail_visibility', type='text'},
        { column = 'sac_scale', type='text'},
        { column = 'tracktype', type='text'},
        { column = 'highway', type='text'},
        { column = 'name', type='text'},
        { column = 'ref', type='text'},
        { column = 'access', type='text'},
        { column = 'incline', type='text'},
        { column = 'surface', type='text'},
        { column = 'ford', type='bool'},
        { column = 'tags', type = 'jsonb' },
        { column = 'geom', type = 'linestring' },
    }
})

local hiking_routes = osm2pgsql.define_table({
    name = 'hiking_routes',
    schema = 'public',
    ids = { type = 'any', type_column = 'osm_type', id_column = 'osm_id' },
    columns = {
        {column = 'id', sql_type = 'serial', create_only = true},
        { column = 'updated_at_osm'},
        { column = 'updated_at'},
        { column = 'name', type = 'text' },
        { column = 'cai_scale', type = 'text' },
        { column = 'osm2cai_status', type = 'integer'},
        { column = 'score', type = 'integer'},
        { column = 'osmc_symbol', type = 'text' },
        { column = 'network', type= 'text' },
        { column = 'survey_date', type= 'text' },
        { column = 'roundtrip', type= 'text' },
        { column = 'symbol', type= 'text' },
        { column = 'symbol_it', type= 'text' },
        { column = 'ascent', type= 'text' },
        { column = 'descent', type= 'text' },
        { column = 'distance', type= 'text' },
        { column = 'duration_forward', type= 'text' },
        { column = 'duration_backward', type= 'text' },
        { column = 'from', type= 'text' },
        { column = 'to', type= 'text' },
        { column = 'rwn_name', type= 'text' },
        { column = 'ref_REI', type= 'text' },
        { column = 'maintenance', type= 'text' },
        { column = 'maintenance_it', type= 'text' },
        { column = 'operator', type= 'text' },
        { column = 'state', type= 'text' },
        { column = 'ref', type= 'text' },
        { column = 'source', type= 'text' },
        { column = 'source_ref', type= 'text' },
        { column = 'note', type= 'text' },
        { column = 'note_it', type= 'text' },
        { column = 'old_ref', type= 'text' },
        { column = 'note_project_page', type= 'text' },
        { column = 'website', type= 'text' },
        { column = 'wikimedia_commons', type= 'text' },
        { column = 'description', type= 'text' },
        { column = 'description_it', type= 'text' },
        { column = 'tags', type = 'jsonb'},
        { column = 'geom', type = 'multilinestring', projection = 4326 },
        { column = 'members', type = 'jsonb' },
        { column = 'members_ids', type = 'text'}
    }
})

local places = osm2pgsql.define_table({
    name = 'places',
    ids = { type = 'any', type_column = 'osm_type', id_column = 'osm_id' },
    columns = {
        { column = 'id', sql_type = 'serial', create_only = true},
        { column = 'updated_at'},
        { column = 'name' },
        { column = 'class', not_null = true },
        { column = 'subclass' },
        { column = 'geom', type = 'point', not_null = true},
        { column = 'tags', type = 'jsonb' },
        { column = 'elevation', type = 'int' },
        { column = 'score', type = 'int'}
    }
})

local poles = osm2pgsql.define_table({
    name = 'poles',
    ids = { type = 'any', type_column = 'osm_type', id_column = 'osm_id' },
    columns = {
        { column = 'id', sql_type = 'serial', create_only = true},
        { column = 'updated_at' },
        { column = 'name' },
        { column = 'tags', type = 'jsonb' },
        { column = 'geom', type = 'point' },
        { column = 'ref' },
        { column = 'ele' },
        { column = 'destination' },
        { column = 'support' },
        { column = 'elevation', type = 'int' },
        { column = 'score', type = 'int'},
    }
})

function format_timestamp(unix_timestamp)
    return os.date('%Y-%m-%d %H:%M:%S', unix_timestamp)
end

function process_admin_area(object)
    if object.tags.boundary ~= 'administrative' then
        return
    end

    local admin_level = object.tags.admin_level or nil
    local score = 0

    if object.tags.name then
        score = score + 1
    end
    if object.tags.wikidata then
        score = score + 1
    end
    if object.tags.wikipedia then
        score = score + 1
    end
    if object.tags.wikimedia_commons then
        score = score + 1
    end

    local a = {
        updated_at = format_timestamp(object.timestamp) or nil,
        name = object.tags.name or 'unknown',
        tags = object.tags,
        geom = object:as_multipolygon(),
        admin_level = admin_level,
        score = score,
    }

    admin_areas:insert(a)
end

function process_hiking_route(object, geom)
    local osm2cai_status = 0
    local score = 0

    local cai_scale_present = object.tags['cai_scale'] ~= nil
    local survey_cai_present = object.tags.source and string.match(object.tags.source, "survey:CAI") ~= nil

    -- calculate osm2cai status value -- 
    if cai_scale_present and survey_cai_present then
        osm2cai_status = 3
    elseif cai_scale_present then
        osm2cai_status = 1
    elseif survey_cai_present then
        osm2cai_status = 2
    end

    -- calculate score value --
    if object.tags.name then
        score = score + 1
    end
    if object.tags.wikidata then
        score = score + 1
    end
    if object.tags.wikipedia then
        score = score + 1
    end
    if object.tags.wikimedia_commons then
        score = score + 1
    end
    if object.tags.ref then
        score = score + 1
    end

    -- add osm2cai status to score
    score = score + osm2cai_status

    -- get the members_id from object.members ref
    local members_ids = ''
    if object.members then
        for i, member in ipairs(object.members) do
            if member.type and member.type == 'w' then
                if member.ref then
                    members_ids = members_ids .. tostring(member.ref) .. ','
                end
            end
        end
    end
    --remove final comma
    if members_ids ~= '' then
        members_ids = string.sub(members_ids, 1, -2)
    end

    local a = {
        name = object.tags.name,
        updated_at_osm = format_timestamp(object.timestamp) or nil,
        cai_scale = object.tags['cai_scale'],
        osm2cai_status = osm2cai_status,
        score = score,
        osmc_symbol = object.tags['osmc:symbol'],
        network = object.tags.network,
        survey_date = object.tags['survey:date'],
        roundtrip = object.tags.roundtrip,
        symbol = object.tags.symbol,
        symbol_it = object.tags['symbol:it'],
        ascent = object.tags.ascent,
        descent = object.tags.descent,
        distance = object.tags.distance,
        duration_forward = object.tags['duration:forward'],
        duration_backward = object.tags['duration:backward'],
        from = object.tags.from,
        to = object.tags.to,
        rwn_name = object.tags['rwn:name'],
        ref_REI = object.tags['ref:REI'],
        maintenance = object.tags.maintenance,
        maintenance_it = object.tags['maintenance:it'],
        operator = object.tags.operator,
        state = object.tags.state,
        ref = object.tags.ref,
        source = object.tags.source,
        source_ref = object.tags['source:ref'],
        note = object.tags.note,
        note_it = object.tags['note:it'],
        old_ref = object.tags['old_ref'],
        note_project_page = object.tags['note:project_page'],
        website = object.tags.website,
        wikimedia_commons = object.tags['wikimedia_commons'],
        description = object.tags.description,
        description_it = object.tags['description:it'],
        tags = object.tags,
        geom = geom,
        members = object.members,
        members_ids = members_ids
    }

    hiking_routes:insert(a)
end

function process_hiking_route_way(object, geom)
    local a = {
        updated_at = format_timestamp(object.timestamp) or nil,
        trail_visibility = object.tags.trail_visibility,
        sac_scale = object.tags.sac_scale,
        tracktype = object.tags.tracktype,
        highway = object.tags.highway,
        name = object.tags.name,
        ref = object.tags.ref,
        access = object.tags.access,
        incline = object.tags.incline,
        surface = object.tags.surface,
        ford = object.tags.ford,
        tags = object.tags,
        geom = geom,
    }
    hiking_routes_ways:insert(a)
end

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
            { key = 'tourism', values = { artwork = 'artwork', alpine_hut = 'alpine_hut', wilderness_hut = 'wilderness_hut', aquarium = 'aquarium', camp_site = 'camp_site', caravan_site = 'caravan_site', picnic_site = 'picnic_site', hostel = 'hostel', museum = 'museum', zoo = 'zoo', theme_park = 'theme_park', artwork = 'artwork' }, class = 'tourism' },
            { key = 'historic', values = { memorial = 'memorial', wayside_shrine = 'wayside_shrine', wayside_cross = 'wayside_cross', monastery = 'monastery', archaeological_site = 'archaeological_site', castle = 'castle', farm = 'farm', fort = 'fort', manor = 'manor', tower = 'tower', city_gate = 'city_gate', church = 'church', ruins = 'ruins'}, class = 'historic' },
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

function process_pole(object)
    -- Poles ufficiali
    local is_official = object.tags.tourism == 'information' and object.tags.information == 'guidepost'

    -- Poles proposti
    local is_proposed = object.tags.proposed == 'yes'
        and object.tags['proposed:information'] == 'guidepost'
        and object.tags['proposed:tourism'] == 'information'

    if not is_official and not is_proposed then
        return
    end

    -- calculate score value --
    local score = 0

    if object.tags.name then
        score = score + 1
    end
    if object.tags.wikidata then
        score = score + 1
    end
    if object.tags.wikipedia then
        score = score + 1
    end
    if object.tags.wikimedia_commons then
        score = score + 1
    end

    local a = {
        updated_at = format_timestamp(object.timestamp) or nil,
        name = object.tags.name,
        tags = object.tags,
        geom = object:as_point(),
        ref = object.tags.ref,
        ele = object.tags.ele,
        destination = object.tags.destination,
        support = object.tags.support,
        elevation = object.tags.ele or nil,
        score = score
    }
    poles:insert(a)
end

function osm2pgsql.process_node(object)
    process_pole(object)
    process_place(object, object:as_point())
end

function osm2pgsql.process_way(object)
    if object.is_closed then
        process_place(object, object:as_polygon():centroid())
    end
     if object.tags.highway == 'footway' or object.tags.highway == 'path' or object.tags.highway == 'track' or object.tags.highway == 'steps' then
        process_hiking_route_way(object, object:as_linestring())
    end
end

function osm2pgsql.process_relation(object)
    if object.tags.boundary == 'administrative' then
        process_admin_area(object)
    end
    if object.tags.type == 'route' and object.tags.route == 'hiking' then
        local geom = object:as_multilinestring() --use multilinestring for relations
        if geom then
            process_hiking_route(object, geom) 
        end
    end
    if object.tags.type == 'multipolygon' then
        process_place(object, object:as_multipolygon():centroid())
    end
   
end
