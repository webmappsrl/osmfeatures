local admin_areas = osm2pgsql.define_table({
    name = 'admin_areas',
    ids = { type = 'any', type_column = 'osm_type', id_column = 'osm_id' },
    columns = {
        { column = 'updated_at' },
        { column = 'name' },
        { column = 'tags', type = 'jsonb' },
        { column = 'geom', type = 'multipolygon' },
        { column = 'admin_level' }
    }
})

function format_timestamp(unix_timestamp)
    return os.date('%Y-%m-%d %H:%M:%S', unix_timestamp)
end

function process_admin_area(object)
    if object.tags.boundary ~= 'administrative' then
        return
    end

    local admin_level = object.tags.admin_level or 'unknown'

    local a = {
        updated_at = format_timestamp(object.timestamp) or nil,
        name = object.tags.name or 'unknown',
        tags = object.tags,
        geom = object:as_multipolygon(),
        admin_level = admin_level
    }

    admin_areas:insert(a)
end

function osm2pgsql.process_relation(object)
    process_admin_area(object)
end