<script src="<?=get_bloginfo('wpurl').'/wp-content/plugins/wp-wtyd/js/jquery.flot.js'?>"></script>
<style>

#wtyd_graph { padding: 20px; }

.blog_stats	{ height: 240px; width: 430px; float:left; }
#graph_nav	{ padding: 5px 10px 10px; font-size: 12px; }
#graph_nav select{ vertical-align:middle; }
#graph_nav .buffer{ padding-left: 20px;}
#graph_lines	{ display: block; }
#graph_plot		{ display: none; }

.blog_data	{ font-size: 10px; position: relative; margin:0 24px; width:95px; height: 240px; float:left; }
.blog_data strong { display: block; margin: 6px 0px; font-size: 12px;}
.blog_data div	{ padding: 1px; }
.blog_data .legend		{ left:-2px; position:absolute; top:170px; width:260px; }
.blog_data .legend .legendLabel	{ width: 120px; }
.blog_data .legend td	{ padding: 1px 0px; }
.blog_data div input	{ margin-right:3px; position:relative; top:2px; }

</style>