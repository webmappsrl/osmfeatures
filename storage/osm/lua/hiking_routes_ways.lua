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
        { column = 'geom', type = 'linestring', projection = 4326},

    }
})

function osm2pgsql.process_way(object)
    if not object.tags.highway then
        return
    end
    local row = {
    updated_at = os.date('%Y-%m-%d %H:%M:%S', object.timestamp) or nil,
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
	tags = object.tags.tags
    }
    hiking_routes_ways:insert(row)
end