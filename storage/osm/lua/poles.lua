local poles = osm2pgsql.define_table({
    name = 'poles',
    ids = { type = 'any', type_column = 'osm_type', id_column = 'osm_id' },
    columns = {
        { column = 'id', sql_type = 'serial', create_only = true},
        { column = 'updated_at' },
        { column = 'name' },
        { column = 'tags', type = 'jsonb' },
        { column = 'geom', type = 'point' projection = 4326 },
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

function process_pole(object)
    if object.tags.tourism ~= 'information' or object.tags.information ~= 'guidepost' then
        return
    end
    local score = 0

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
    if object.tags.ele then
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
        score = score,
    }

    poles:insert(a)
end

function osm2pgsql.process_node(object)
    process_pole(object)
end
