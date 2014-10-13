$css('qtip');
$js(true,[
	'jquery',
	'qtip',
	'cytoscape',
	'cytoscape-qtip',
],function(){
	$('graph-erm').each(function(){
		
		// create the model for the E-R diagram
		var nodeDataArray = [];
		var linkDataArray = [];
		var THIS = $(this);
		THIS.find('table').each(function(){
			var cols = [];
			$(this).find('col').each(function(){
				cols.push($(this).attr('name'));
			});
			var name = $(this).attr('name');
			nodeDataArray.push({
				'data':{
					'id':name,
					'name':name,
					'theCols':cols
				}
			});
		});
		THIS.find('link').each(function(){
			linkDataArray.push({
				'data':{
					'source':$(this).attr('from'),
					'target':$(this).attr('to'),
					'relation':$(this).attr('relation')
				}
			});
		});
		//console.log(nodeDataArray);
		//console.log(linkDataArray);

		var layouts = {
			concentric: {
				name: 'concentric',
				fit: true, // whether to fit the viewport to the graph
				ready: undefined, // callback on layoutready
				stop: undefined, // callback on layoutstop
				padding: 30, // the padding on fit
				startAngle: 3/2 * Math.PI, // the position of the first node
				counterclockwise: false, // whether the layout should go counterclockwise (true) or clockwise (false)
				minNodeSpacing: 10, // min spacing between outside of nodes (used for radius adjustment)
				height: undefined, // height of layout area (overrides container height)
				width: undefined, // width of layout area (overrides container width)
				concentric: function(){ // returns numeric value for each node, placing higher nodes in levels towards the centre
				  return this.degree();
				},
				levelWidth: function(nodes){ // the variation of concentric values in each level
				  return nodes.maxDegree() / 4;
				}
			},
			breadthfirst: {
				name: 'breadthfirst',
				fit: true, // whether to fit the viewport to the graph
				directed: false, // whether the tree is directed downwards (or edges can point in any direction if false)
				padding: 30, // padding on fit
				circle: false, // put depths in concentric circles if true, put depths top down if false
				roots: undefined, // the roots of the trees
				maximalAdjustments: 0 // how many times to try to position the nodes in a maximal way (i.e. no backtracking)
			},
			circle: {
				name: 'circle',
				fit: true, // whether to fit the viewport to the graph
				rStepSize: 10, // the step size for increasing the radius if the nodes don't fit on screen
				padding: 30, // the padding on fit
				startAngle: 3/2 * Math.PI, // the position of the first node
				counterclockwise: false // whether the layout should go counterclockwise (true) or clockwise (false)
			},
			cose: {
				name: 				'cose',
				ready               : function() {},
				stop                : function() {},
				refresh             : 0, // Number of iterations between consecutive screen positions update (0 -> only updated on the end)
				fit                 : true,  // Whether to fit the network view after when done
				padding             : 30,  // Padding on fit
				randomize           : true, // Whether to randomize node positions on the beginning
				debug               : false, // Whether to use the JS console to print debug messages
				nodeRepulsion       : 10000, // Node repulsion (non overlapping) multiplier
				nodeOverlap         : 100, // Node repulsion (overlapping) multiplier
				idealEdgeLength     : 10, // Ideal edge (non nested) length
				edgeElasticity      : 10, // Divisor to compute edge forces
				nestingFactor       : 5,  // Nesting factor (multiplier) to compute ideal edge length for nested edges
				gravity             : 450,  // Gravity force (constant)
				numIter             : 100, // Maximum number of iterations to perform
				initialTemp         : 200, // Initial temperature (maximum node displacement)
				coolingFactor       : 0.95,  // Cooling factor (how the temperature is reduced between consecutive iterations
				minTemp             : 1 // Lower temperature threshold (below this point the layout will end)
			},
		};
		for(var k in layouts)
			$(this).before('<a class="layout-apply" data-layout="'+k+'">'+k+'</a>');
		
		var elements = {
			nodes: nodeDataArray,
			edges: linkDataArray
		};
		$(this).empty().cytoscape({
			style: cytoscape.stylesheet()
				.selector('node')
					.css({
						'content': 'data(name)',
						'font-size': '1em',
						'text-valign': 'center',
						'color': '#FFF',
						'text-outline-width': 0.2,
						'text-outline-color': '#888',
						'height': '6em',
						'width': '6em',
						'background-color': '#86B342',
					})
				.selector('edge')
					.css({
						'target-arrow-shape': 'triangle',
						'color': '#888',
					})
				.selector('edge[relation="shared"]')
					.css({
						'source-arrow-shape': 'triangle'
					})
				.selector(':selected')
					.css({
						'background-color': 'black',
						'line-color': 'black',
						'target-arrow-color': 'black',
						'source-arrow-color': 'black'
					})
				.selector('.faded')
					.css({
						'opacity': 0,
						'text-opacity': 0
					}),
			elements: elements,
			ready: function(){
				var cy = this;
				cy.elements().unselectify();
				cy.on('tap', 'node', function(e){
					var node = e.cyTarget; 
					var neighborhood = node.neighborhood().add(node);
					cy.elements().addClass('faded');
					neighborhood.removeClass('faded');
				});
				cy.on('tap', function(e){
					if(e.cyTarget===cy)
						cy.elements().removeClass('faded');
				});
				for(var i in nodeDataArray){
					var v = nodeDataArray[i].data;
					var html = '';
					if(v.theCols&&v.theCols.length)
						html += '<ul class="erm-column"><li>'+v.theCols.join('</li><li>')+'</li></ul>';
					var el = cy.elements('#'+v.id);
					if(html)
						el.qtip({
							content: html,
							position: {
								my: 'top center',
								at: 'bottom center'
							},
							style: {
								classes: 'qtip-bootstrap',
								tip: {
									width: 16,
									height: 8
								}
							}
						});
				}
				cy.layout(layouts['breadthfirst']);
				$('a.layout-apply').click(function(e){
					e.preventDefault();
					var k = $(this).attr('data-layout');
					cy.layout(layouts[k]);
					return false;
				});
			}
		});	
		
	});
});