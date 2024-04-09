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


function process_hiking_route(object, geom)
    local osm2cai_status = 0

    -- osm2cai_status will be 0 if cai:scale and source=survey:CAI are not present, will be 1 if cai:scale is present and source=survey:CAI is not present, will be 2 if source=survey:CAI is present and cai:scale is not present, will be 3 if both are present
    if object.tags['cai:scale'] and object.tags.source == 'survey:CAI' then
        osm2cai_status = 3
    elseif object.tags['cai_scale'] then
        osm2cai_status = 1
    elseif object.tags.source == 'survey:CAI' then
        osm2cai_status = 2
    end

    local a = {
        name = object.tags.name,
        updated_at_osm = os.date('%Y-%m-%d %H:%M:%S', object.timestamp) or nil,
        cai_scale = object.tags['cai_scale'],
        osm2cai_status = osm2cai_status,
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
   

function osm2pgsql.process_relation(object)
    if object.tags.type == 'route' and object.tags.route == 'hiking' then
        local geom = object:as_multilinestring() --use multilinestring for relations
        if geom then
            process_hiking_route(object, geom) 
        end
    end
end

