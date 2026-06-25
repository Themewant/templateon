<?php
namespace TEMPLATEON\StarterLibrary;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TEMPLA_Templates {

    const TRANSIENT_KEY  = 'templa_templates_cache_v2';
    const FAVORITES_OPT  = 'templa_favorite_templates';
    const SITE_URL       = 'https://wpeasyelements.com/demotemplate/';
    const API_ENDPOINT   = 'https://wpeasyelements.com/demotemplate/wp-json/rtTemplates/v1/starter-templates?package=free';

    public function get_all() {
        $cached = $this->get_cached_payload();

        $templates = array_values( (array) $cached['templates'] );
        $templates = (array) apply_filters( 'templa_available_templates', $templates );

        $free = [];
       
        foreach ( $templates as $template ) {
           
            $free[] = $template;
            
        }

        return array_values( array_merge( $free ) );
    }

    public function get_api_category_tree() {
        $cached = $this->get_cached_payload();
        return $cached['categories_tree'];
    }

    public function sync_library() {
        delete_transient( self::TRANSIENT_KEY );
        $payload = $this->fetch_from_api();
        set_transient( self::TRANSIENT_KEY, $payload, DAY_IN_SECONDS );
        return $payload['templates'];
    }

    private function get_cached_payload() {
        $cached = get_transient( self::TRANSIENT_KEY );

        if ( is_array( $cached ) && isset( $cached['templates'] ) ) {
            return $cached;
        }

        $payload = $this->fetch_from_api();
        set_transient( self::TRANSIENT_KEY, $payload, DAY_IN_SECONDS );
        return $payload;
    }

    private function fetch_from_api() {
        $empty = [ 'templates' => [], 'categories_tree' => [] ];

        $response = wp_remote_get( self::API_ENDPOINT, [ 'timeout' => 20 ] );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return $empty;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! is_array( $body ) || empty( $body['starter_templates'] ) ) {
            return $empty;
        }

        $tree_raw = isset( $body['categories_tree'] ) && is_array( $body['categories_tree'] )
            ? $body['categories_tree']
            : [];

        $alias_map = $this->build_child_alias_map( $tree_raw );

        return [
            'templates'       => $this->normalize_api_data( $body['starter_templates'], $alias_map ),
            'categories_tree' => $tree_raw,
        ];
    }

    /**
     * Build a sanitized-name → [slug,...] map from tree children.
     *
     * The API can return the same logical category twice — once as a top-level
     * entry (e.g. slug "lawyer-attorney") and once as a child under a parent
     * (e.g. slug "lawyer-attorney-business"). Cards reference one slug, the
     * mega-menu uses the other. The alias map lets us tag each card with both.
     */
    private function build_child_alias_map( array $api_tree ) {
        $map = [];
        foreach ( $api_tree as $parent ) {
            if ( empty( $parent['children'] ) || ! is_array( $parent['children'] ) ) {
                continue;
            }
            foreach ( $parent['children'] as $child ) {
                if ( empty( $child['slug'] ) ) {
                    continue;
                }
                $slug = sanitize_title( $child['slug'] );
                $name = isset( $child['name'] ) ? (string) $child['name'] : '';
                if ( '' !== $name ) {
                    $key = sanitize_title( $name );
                    if ( $key && $key !== $slug ) {
                        $map[ $key ][] = $slug;
                    }
                }
            }
        }
        return $map;
    }

    /**
     * Safely read a scalar field from an API item as a string.
     *
     * Guards against a missing key (no "undefined index" notice) and against a
     * non-scalar value such as an array/object (no "Array to string conversion"
     * warning). Anything that is not a string/number/bool collapses to ''.
     */
    private function api_string_field( $item, $key ) {
        if ( ! is_array( $item ) || ! array_key_exists( $key, $item ) ) {
            return '';
        }
        $value = $item[ $key ];
        return is_scalar( $value ) ? (string) $value : '';
    }

    private function normalize_api_data( array $items, array $alias_map = [] ) {
        $normalized = [];

        foreach ( $items as $item ) {
            $slug = ! empty( $item['groups'][0]['slug'] )
                ? $item['groups'][0]['slug']
                : sanitize_title( $item['title'] ?? '' );

            $categories = [];
            foreach ( (array) ( $item['categories'] ?? [] ) as $cat ) {
                $name     = '';
                $cat_slug = '';
                if ( is_array( $cat ) ) {
                    $name     = (string) ( $cat['name'] ?? '' );
                    $cat_slug = ! empty( $cat['slug'] ) ? sanitize_title( (string) $cat['slug'] ) : '';
                } else {
                    $name = (string) $cat;
                }
                if ( '' === $name && '' === $cat_slug ) {
                    continue;
                }
                if ( '' === $cat_slug ) {
                    $cat_slug = sanitize_title( $name );
                }

                $slugs    = [ $cat_slug ];
                $name_key = sanitize_title( $name );
                if ( $name_key && isset( $alias_map[ $name_key ] ) ) {
                    $slugs = array_merge( $slugs, $alias_map[ $name_key ] );
                }

                $categories[] = [
                    'name'  => $name,
                    'slugs' => array_values( array_unique( array_filter( $slugs ) ) ),
                ];
            }

            $normalized[] = [
                'id'               => $item['id'] ?? 0,
                'slug'             => $slug,
                'name'             => $item['title'] ?? '',
                'categories'       => $categories,
                'default_homepage' => 'Home',
                'xml'              => $this->api_string_field( $item, 'xml' ),
                'kit'              => $this->api_string_field( $item, 'kit' ),
                'settings'         => $this->api_string_field( $item, 'settings' ),
                'form'             => $this->api_string_field( $item, 'form' ),
                'thumb'            => $item['thumbnail_url'] ?? '',
                'preview'          => $item['live_demo_url'] ?? '',
                'badge'            => $item['badge'] ?? '',
                'template_type'    => $item['template_type'] ?? 'page',
            ];
        }

        return $normalized;
    }

    public function get_categories() {
        $templates = $this->get_all();
        $cats      = [];
        foreach ( $templates as $template ) {
            if ( empty( $template['categories'] ) ) {
                continue;
            }
            foreach ( (array) $template['categories'] as $cat ) {
                if ( is_array( $cat ) ) {
                    $label = (string) ( $cat['name'] ?? '' );
                    $slug  = ! empty( $cat['slugs'][0] ) ? $cat['slugs'][0] : sanitize_title( $label );
                } else {
                    $label = (string) $cat;
                    $slug  = sanitize_title( $label );
                }
                if ( ! $slug ) {
                    continue;
                }
                if ( ! isset( $cats[ $slug ] ) ) {
                    $cats[ $slug ] = [ 'slug' => $slug, 'label' => $label, 'count' => 0 ];
                }
                $cats[ $slug ]['count']++;
            }
        }
        ksort( $cats );
        return array_values( $cats );
    }

    /**
     * Build a parent → children category tree.
     *
     * Priority order:
     *   1. Hierarchy provided by the REST API (`categories_tree`).
     *   2. Manual mapping via the `templa_category_children_map` filter:
     *        add_filter( 'templa_category_children_map', function () {
     *            return [ 'business' => [ 'finance', 'insurance' ] ];
     *        } );
     *
     * @return array  [ [ slug, label, count, children: [...] ], ... ]
     */
    public function get_category_tree() {
        $api_tree = $this->get_api_category_tree();

        if ( ! empty( $api_tree ) ) {
            $tree = $this->normalize_api_tree( $api_tree );
            $tree = $this->filter_tree_to_used( $tree );
            return apply_filters( 'templa_category_tree', $tree, [] );
        }

        $flat         = $this->get_categories();
        $children_map = (array) apply_filters( 'templa_category_children_map', [] );

        $by_slug = [];
        foreach ( $flat as $c ) {
            $by_slug[ $c['slug'] ] = $c;
        }

        $child_slugs = [];
        foreach ( $children_map as $children ) {
            foreach ( (array) $children as $child_slug ) {
                $child_slugs[ sanitize_title( $child_slug ) ] = true;
            }
        }

        $tree = [];
        foreach ( $flat as $cat ) {
            if ( isset( $child_slugs[ $cat['slug'] ] ) ) {
                continue;
            }

            $children = [];
            if ( ! empty( $children_map[ $cat['slug'] ] ) ) {
                foreach ( (array) $children_map[ $cat['slug'] ] as $child_slug ) {
                    $child_slug = sanitize_title( $child_slug );
                    if ( isset( $by_slug[ $child_slug ] ) ) {
                        $children[] = $by_slug[ $child_slug ];
                    }
                }
            }

            $cat['children'] = $children;
            $tree[]          = $cat;
        }

        return apply_filters( 'templa_category_tree', $tree, $flat );
    }

    /**
     * Map raw API tree nodes (id, slug, name, count, parent, children)
     * to the shape used by the admin template (slug, label, count, children).
     */
    private function normalize_api_tree( array $api_tree ) {
        $collect_descendant_keys = function ( array $nodes ) use ( &$collect_descendant_keys ) {
            $keys = [];
            foreach ( $nodes as $node ) {
                if ( empty( $node['children'] ) || ! is_array( $node['children'] ) ) {
                    continue;
                }
                foreach ( $node['children'] as $child ) {
                    if ( ! empty( $child['slug'] ) ) {
                        $keys[ 'slug:' . sanitize_title( $child['slug'] ) ] = true;
                    }
                    if ( ! empty( $child['name'] ) ) {
                        $keys[ 'name:' . sanitize_title( $child['name'] ) ] = true;
                    }
                    foreach ( $collect_descendant_keys( [ $child ] ) as $k => $v ) {
                        $keys[ $k ] = $v;
                    }
                }
            }
            return $keys;
        };

        $descendant_keys = $collect_descendant_keys( $api_tree );

        $out = [];
        foreach ( $api_tree as $parent ) {
            if ( empty( $parent['slug'] ) ) {
                continue;
            }
            $parent_slug = sanitize_title( $parent['slug'] );
            $parent_name = isset( $parent['name'] ) ? (string) $parent['name'] : '';
            $name_key    = $parent_name ? sanitize_title( $parent_name ) : '';

            if ( isset( $descendant_keys[ 'slug:' . $parent_slug ] )
                || ( $name_key && isset( $descendant_keys[ 'name:' . $name_key ] ) )
            ) {
                continue;
            }

            $children = [];
            if ( ! empty( $parent['children'] ) && is_array( $parent['children'] ) ) {
                foreach ( $parent['children'] as $child ) {
                    if ( empty( $child['slug'] ) ) {
                        continue;
                    }
                    $children[] = [
                        'slug'  => sanitize_title( $child['slug'] ),
                        'label' => isset( $child['name'] ) ? (string) $child['name'] : '',
                        'count' => isset( $child['count'] ) ? (int) $child['count'] : 0,
                    ];
                }
            }
            $out[] = [
                'slug'     => $parent_slug,
                'label'    => $parent_name,
                'count'    => isset( $parent['count'] ) ? (int) $parent['count'] : 0,
                'children' => $children,
            ];
        }
        return $out;
    }

    /**
     * Slugs that at least one loaded template is actually tagged with (aliases
     * included). Mirrors the card's data-categories, so a category only counts
     * as "used" when clicking it would genuinely reveal templates.
     *
     * @return array  [ slug => true, ... ]
     */
    private function get_used_category_slugs() {
        $used = [];
        foreach ( $this->get_all() as $template ) {
            foreach ( (array) ( $template['categories'] ?? [] ) as $cat ) {
                $slugs = is_array( $cat ) ? (array) ( $cat['slugs'] ?? [] ) : [ $cat ];
                foreach ( $slugs as $slug ) {
                    $slug = sanitize_title( (string) $slug );
                    if ( '' !== $slug ) {
                        $used[ $slug ] = true;
                    }
                }
            }
        }
        return $used;
    }

    /**
     * Hide categories that have no templates. A child survives only if its slug
     * is used; a parent survives if it keeps at least one child or is itself
     * used (then it renders as a clickable leaf chip).
     *
     * @param array $tree  Normalized tree from normalize_api_tree().
     * @return array
     */
    private function filter_tree_to_used( array $tree ) {
        $used = $this->get_used_category_slugs();
        $out  = [];

        foreach ( $tree as $node ) {
            $children = [];
            foreach ( (array) ( $node['children'] ?? [] ) as $child ) {
                if ( ! empty( $child['slug'] ) && isset( $used[ $child['slug'] ] ) ) {
                    $children[] = $child;
                }
            }
            $parent_used = ! empty( $node['slug'] ) && isset( $used[ $node['slug'] ] );

            if ( ! empty( $children ) && $parent_used ) {
                array_unshift( $children, [
                    'slug'  => $node['slug'],
                    'label' => $node['label'],
                    'count' => isset( $node['count'] ) ? (int) $node['count'] : 0,
                ] );
            }

            $node['children'] = $children;

            if ( ! empty( $children ) || $parent_used ) {
                $out[] = $node;
            }
        }

        return $out;
    }

    public function get_favorites() {
        $favs = get_option( self::FAVORITES_OPT, [] );
        return is_array( $favs ) ? $favs : [];
    }

    public function toggle_favorite( $slug ) {
        $favs = $this->get_favorites();
        $slug = sanitize_key( $slug );
        if ( in_array( $slug, $favs, true ) ) {
            $favs = array_values( array_diff( $favs, [ $slug ] ) );
            $state = 'removed';
        } else {
            $favs[] = $slug;
            $state  = 'added';
        }
        update_option( self::FAVORITES_OPT, $favs );
        return [ 'state' => $state, 'favorites' => $favs ];
    }
}
