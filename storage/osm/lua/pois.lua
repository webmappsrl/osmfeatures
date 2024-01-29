local pois = osm2pgsql.define_table({
    name = 'pois',
    ids = { type = 'any', type_column = 'osm_type', id_column = 'osm_id' },
    columns = {
        { column = 'updated_at'},
        { column = 'name' },
        { column = 'class', not_null = true },
        { column = 'subclass' },
        { column = 'geom', type = 'point', not_null = true },
        { column = 'tags', type = 'jsonb' },
}})

function format_timestamp(unix_timestamp)
    return os.date('%Y-%m-%d %H:%M:%S', unix_timestamp)
end

function process_poi(object, geom)
    local a = {
        updated_at = object.timestamp or nil,
        name = object.tags.name,
        geom = geom,
        tags = object.tags,
    }

    if object.tags.amenity and ( 
           object.tags.amenity == 'place_of_worship' or 
           object.tags.amenity == 'drinking_water' 
        )
        then
        a.class = 'amenity'
        a.subclass = object.tags.amenity
    elseif object.tags.natural and (
           object.tags.natural == 'peak' or
           object.tags.natural == 'spring'
        )
        then
        a.class = 'natural'
        a.subclass = object.tags.natural   
    else
        return
    end

    pois:insert(a)
end

function osm2pgsql.process_node(object)
    process_poi(object, object:as_point())
end

function osm2pgsql.process_way(object)
    if object.is_closed and object.tags.building then
        process_poi(object, object:as_polygon():centroid())
    end
end