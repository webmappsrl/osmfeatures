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
    }
})

local hiking_ways = osm2pgsql.define_table({
    name = 'hiking_ways',
    schema = 'public',
    ids = { type = 'any', type_column = 'osm_type', id_column = 'osm_id' },
    columns = {
        {column = 'id', sql_type = 'serial', create_only = true},
         { column = 'updated_at'},
        { column = 'trail_visibility', type='text'},
        { column = 'sac_scale', type='text'},
        {column = 'tracktype', type='text'},
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


function process_hiking_route(object, geom)

    local a = {
        name = object.tags.name,
        updated_at_osm = os.date('%Y-%m-%d %H:%M:%S', object.timestamp) or nil,
        cai_scale = object.tags['cai:scale'],
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
    }
    hiking_routes:insert(a)
end

function osm2pgsql.process_way(object)
    if not object.tags.highway then
        return
    end
    local a = {
        updated_at = os.date('%Y-%m-%d %H:%M:%S', object.timestamp) or nil,
        trail_visibility = object.tags['trail_visibility'],
        sac_scale = object.tags['sac_scale'],
        tracktype = object.tags['tracktype'],
        highway = object.tags.highway,
        name = object.tags.name,
        ref = object.tags.ref,
        access = object.tags.access,
        incline = object.tags.incline,
        surface = object.tags.surface,
        ford = object.tags.ford,
        tags = object.tags,
        geom = object:as_linestring(),
    }
    hiking_ways:insert(a)
end

   

function osm2pgsql.process_relation(object)
    if object.tags.type == 'route' and object.tags.route == 'hiking' then
        local geom = object:as_multilinestring() --use multilinestring for relations
        if geom then
            process_hiking_route(object, geom) 
        end
    end
end

