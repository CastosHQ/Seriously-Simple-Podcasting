<?php


?>
<div style="padding-right: 20px;overflow:hidden;">
    <h1 style="width:70%;float:left"><?php echo __( 'Podcast Analytics', 'seriously-simple-podcasting' ); ?></h1>
    <div style="overflow: auto; text-align:right; padding-top:20px;">
        <div style="width:100%;">
            <div style="width:45%;float:left; text-align:left">
                <input type="tex" class="ssp-date" placeholder="From">
            </div>
            <div style="width: 10%; float:left;text-align:center">
                -
            </div>
            <div style="width:45%;float:left;text-align:right">
                <input type="text" class="ssp-date" placeholder="To">
            </div>
        </div>
    </div>
</div>
<hr>

<?php
$series = get_terms( 'series', array( 'hide_empty' => false ) );

if ( ! empty( $series ) ) {

    if ( isset( $_GET['feed-series'] ) && $_GET['feed-series'] && 'all' != $_GET['feed-series'] ) {
        $current_series = esc_attr( $_GET['feed-series'] );
        $series_class   = '';
    } else {
        $current_series = 'all';
        $series_class   = 'current';
    }

    $html .= '<div class="feed-series-list-container">' . "\n";
    $html .= '<span id="feed-series-toggle" class="series-open" title="' . __( 'Toggle series list display', 'seriously-simple-podcasting' ) . '"></span>' . "\n";

    $html .= '<ul id="feed-series-list" class="subsubsub series-open">' . "\n";
    $html .= '<li><a href="' . add_query_arg( array(
            'feed-series' => 'all',
            'settings-updated' => false
        ) ) . '" class="' . $series_class . '">' . __( 'All Series', 'seriously-simple-podcasting' ) . '</a></li>';

    foreach ( $series as $s ) {

        if ( $current_series == $s->slug ) {
            $series_class = 'current';
        } else {
            $series_class = '';
        }

        $html .= '<li>' . "\n";
        $html .= ' | <a href="' . esc_url( add_query_arg( array(
                'feed-series'      => $s->slug,
                'settings-updated' => false
            ) ) ) . '" class="' . $series_class . '">' . $s->name . '</a>' . "\n";
        $html .= '</li>' . "\n";
    }

    $html .= '</ul>' . "\n";
    $html .= '<br class="clear" />' . "\n";
    $html .= '</div>' . "\n";

    echo $html;
}

?>

<div style="padding-right: 20px;overflow:hidden;">

    <div style="overflow:hidden;padding:20px;">
        <h3 style="margin-bottom: 5px;text-align: left;">Total Listens</h3>
        <div id="tester" style="height:250px;"></div>
    </div>

    <div style="overflow:hidden">

        <div style="width:33.33%;float:left;">
            <div style="padding: 20px; overflow:hidden;">
                <div style="overflow: hidden; background:#fff;">
                    <h3 style="padding: 5px 0; text-align: center">Episode Stats</h3>
                </div>
            </div>
        </div>

        <div style="width:33.33%;float:left;">
            <div style="padding: 20px; overflow:hidden;">
                <div style="overflow: hidden; background:#fff;">
                    <h3 style="padding: 5px 0; text-align: center">Listening Source</h3>
                </div>
            </div>
        </div>

        <div style="width:33.33%;float:left;">
            <div style="padding: 20px; overflow:hidden;">
                <div style="overflow: hidden; background:#fff;">
                    <h3 style="padding: 5px 0; text-align: center">Geographic</h3>
                </div>
            </div>
        </div>

    </div>

</div>

<?php
    add_action( 'admin_footer', function(){
        ?>
            <script>
                TESTER = document.getElementById('tester');
                Plotly.plot(
                    TESTER, [
                        {
                            x: [1, 2, 3, 4, 5],
                            y: [1, 2, 4, 8, 16],
                        }
                    ],
                    {
                        margin: {
                            t: 25,
                            b: 25,
                            r: 25,
                            l: 25
                        },

                    }
                );
                jQuery( '.ssp-date' ).datepicker();
            </script>
        <?php
    } );
?>