local poles = osm2pgsql.define_table({
    name = 'poles',
    ids = { type = 'any', type_column = 'osm_type', id_column = 'osm_id' },
    columns = {
        { column = 'updated_at' },
        { column = 'name' },
        { column = 'tags', type = 'jsonb' },
        { column = 'geom', type = 'point' },
        { column = 'ref' },
        { column = 'ele' },
        { column = 'destination' },
        { column = 'support' }
    }
})

function format_timestamp(unix_timestamp)
    return os.date('%Y-%m-%d %H:%M:%S', unix_timestamp)
end

function process_pole(object)
    if object.tags.tourism ~= 'information' or object.tags.information ~= 'guidepost' then
        return
    end

    local a = {
        updated_at = format_timestamp(object.timestamp) or nil,
        name = object.tags.name,
        tags = object.tags,
        geom = object:as_point(),
        ref = object.tags.ref,
        ele = object.tags.ele,
        destination = object.tags.destination,
        support = object.tags.support
    }

    poles:insert(a)
end

function osm2pgsql.process_node(object)
    process_pole(object)
end
