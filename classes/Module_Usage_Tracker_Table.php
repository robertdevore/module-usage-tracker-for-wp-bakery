<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class ModuleUsageListTable extends WP_List_Table {

    private $modules;

    public function __construct( $modules ) {
        parent::__construct( [
            'singular' => esc_html__( 'Module', 'module-usage-tracker-wp-bakery' ),
            'plural'   => esc_html__( 'Modules', 'module-usage-tracker-wp-bakery' ),
            'ajax'     => false
        ]);

        $this->modules = $modules;
    }

    /**
     * Define the columns for the table.
     *
     * @return array Columns array.
     */
    public function get_columns() {
        return [
            'module_name'  => __( 'Module Name', 'module-usage-tracker-wp-bakery' ),
            'usage_count'  => __( 'Usage Count', 'module-usage-tracker-wp-bakery' ),
            'view_details' => __( 'View Details', 'module-usage-tracker-wp-bakery' )
        ];
    }

    /**
     * Define the sortable columns.
     *
     * @return array Sorted columns.
     */
    public function get_sortable_columns() {
        return [
            'module_name'  => ['name', true],   // Map 'module_name' to 'name'
            'usage_count'  => ['count', false], // Map 'usage_count' to 'count'
        ];
    }
    

    /**
     * Prepare the items for display.
     */
    public function prepare_items() {
        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [$columns, $hidden, $sortable];
        usort( $this->modules, [$this, 'sort_data'] );

        $per_page     = 25;
        $current_page = $this->get_pagenum();
        $total_items  = count( $this->modules );

        $this->modules = array_slice( $this->modules, ( $current_page - 1 ) * $per_page, $per_page );
        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page )
        ]);

        $this->items = $this->modules;
    }

    /**
     * Default column rendering.
     *
     * @param array  $item        The current item.
     * @param string $column_name The column name.
     * @return string Content for the column.
     */
    protected function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'module_name':
                return esc_html( $item['name'] );
            case 'usage_count':
                return esc_html( $item['count'] );
            case 'view_details':
                return sprintf(
                    '<a href="#" class="view-details" data-module="%1$s">%2$s</a>',
                    esc_attr( $item['name'] ),
                    esc_html__( 'View Details', 'module-usage-tracker-wp-bakery' )
                );
            default:
                return print_r( $item, true );
        }
    }

    /**
     * Sort data by column.
     *
     * @param array $a First item.
     * @param array $b Second item.
     * @return int Comparison result.
     */
    private function sort_data( $a, $b ) {
        // Get sorting parameters
        $orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'name';
        $order   = isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'asc';
    
        // Define a mapping from column identifiers to data keys
        $column_mapping = [
            'module_name'  => 'name',
            'usage_count'  => 'count',
        ];
    
        // Map 'orderby' to the actual data key
        $data_key = isset( $column_mapping[ $orderby ] ) ? $column_mapping[ $orderby ] : 'name';
    
        // Sorting logic
        if ( 'count' === $data_key ) {
            $result = intval( $a[$data_key] ) - intval( $b[$data_key] );
        } else {
            $result = strcmp( strtolower( $a[$data_key] ), strtolower( $b[$data_key] ) );
        }
    
        return ( 'asc' === $order ) ? $result : -$result;
    }    
}
