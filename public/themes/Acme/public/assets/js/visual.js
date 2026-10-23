neopassword = ''
neouser = ''
neourl = 'bolt://neo.hongmengyishu.com:7687'  


let network = null;
let currentDataSet = {
    nodes: new vis.DataSet([]),
    edges: new vis.DataSet([])
};
let isLoading = false;
const MAX_NODES = 2000;
let haloInterval = null;
let currentHaloNodeId = null;
let selectedNodeId = null;
let clickTimeout = null;
let isClickHandled = false;
let menuTimeout = null;  // Variable to store the timeout
let isMenuVisible = false;  // To track if the menu is visible
let focusedNodeId = null;



async function loadAndRenderWork(post_id) {
    toggleLoading(true);
    currentDataSet.nodes.clear();
    currentDataSet.edges.clear();
    
    try {
        const driver = neo4j.driver(
            neourl,
            neo4j.auth.basic(neouser, neopassword),
            { encrypted: 'ENCRYPTION_ON' }
        );
        
        const session = driver.session();
        const result = await session.run(`
            MATCH (post:作品 {artwork_id: $post_id})
            OPTIONAL MATCH (post)-[r]-(connected)
            WITH post, 
                collect({rel: r, node: connected}) AS connections
            UNWIND (connections) AS item
            RETURN 
                post as center,
                item.rel as rel,
                item.node as node
        `, { post_id: post_id });
        
        const { nodes, edges } = transformNeo4jData(result);
        currentDataSet.nodes.update(nodes);
        currentDataSet.edges.update(edges);
        // Ensure that the network is updated with the new data
        if (network) {
            network.setData(currentDataSet);  // Ensure network data is updated
        } else {
            createVisNetwork();  // If network is not initialized, initialize it
        }
        
        await session.close();
        await driver.close();
    } catch (error) {
        showError('查询失败: ' + error.message);
    } finally {
        toggleLoading(false);
    }
}

async function expandNode(nodeId) {
    let driver;
    if (isLoading) return;
    toggleLoading(true);

    try {
            if (currentDataSet.nodes.length >= MAX_NODES) {
                showError('已达到最大节点显示数量');
                return;
            }
        
            const existingPositions = network.getPositions();
            const parentPosition = existingPositions[nodeId];
        
            const node = currentDataSet.nodes.get(nodeId);
            if (!node) throw new Error('节点在数据集中不存在');
            // 显式标记当前中心节点为已展开（必须 update 到 currentDataSet）
            currentDataSet.nodes.update({ id: nodeId, expanded: true });
        
            const queryParamsResult = getNodeQueryParams(node.group);
            if (!queryParamsResult) {
                showError(`无法为类型 "${node.group}" 展开节点，缺少查询配置。`);
                toggleLoading(false);
                return;
            }
            const [labelType, propertyName] = queryParamsResult;
        
            driver = neo4j.driver(
                neourl,
                neo4j.auth.basic(neouser, neopassword),
                { encrypted: 'ENCRYPTION_ON' } // Assuming 'ENCRYPTION_ON' is appropriate, adjust if needed
            );
        
            const session = driver.session();
        
            try {
                // First, get the actual node from the database to verify its existence and properties
                const idResult = await session.run(
                    `MATCH (n) WHERE id(n) = $nodeId RETURN n`,
                    { nodeId: Number(nodeId) } // Ensure nodeId is passed as a number if IDs are numeric
                );
        
                if (idResult.records.length === 0) throw new Error('数据库节点不存在');
                const rawNode = idResult.records[0].get('n');
        
                // Check if the node has the required property for the expansion query
                if (!rawNode.properties.hasOwnProperty(propertyName)) {
                     throw new Error(`节点 (ID: ${nodeId}, Label: ${labelType}) 缺少预期的属性: '${propertyName}' 用于展开查询。`);
                }
                const realValue = rawNode.properties[propertyName]; // Get the actual property value
        
                // --- 1. Define Expansion Rules ---
                // Key: Current node type (Label)
                // Value: Array of allowed neighbor node types (Labels) to expand to
                // If a type has no rule, it defaults to expanding all neighbor types
                const expansionRules = {
                    '作品': ['纹样', '朝代时期', '材质', '工艺','人物'], // e.g., Click '作品', only expand these four types
                    '纹样': ['作品'],                         // e.g., Click '纹样', only expand related '作品'
                    '朝代时期': ['作品'],                     // e.g., Click '朝代时期', only expand related '作品'
                    '材质': ['作品'],                         // e.g., Click '材质', only expand related '作品'
                    // '工艺': [], // If empty array, clicking '工艺' expands nothing
                    // ... Add rules for other labels as needed ...
                };
                console.log(`节点 (ID: ${nodeId}, Label: ${labelType}) `)
        
                // --- 2. Get Expansion Rules for the Current Type ---
                const allowedTargetLabels = expansionRules[labelType]; // Get the list of allowed neighbor types for the current node type
        
                let cypherQuery;
                let queryParameters = { value: realValue }; // Base parameters
        
                // --- 3. Build Cypher Query Based on Rules ---
                if (allowedTargetLabels && allowedTargetLabels.length > 0) {
                    // If specific rules exist and are not empty
                    console.log(`为类型 "${labelType}" 应用扩展规则，只查找: ${allowedTargetLabels.join(', ')}`);
                    cypherQuery = `
                        MATCH (center:${labelType} {${propertyName}: $value})
                        OPTIONAL MATCH (center)-[rel]-(node) // Match all neighbors first
                        WHERE node IS NOT NULL AND size([label IN labels(node) WHERE label IN $allowedTargetLabels]) > 0 // Then filter neighbors based on the allowed labels list
                        RETURN center, rel, node
                        LIMIT 30 // Limit the number of results
                    `;
                    queryParameters.allowedTargetLabels = allowedTargetLabels; // Add allowed labels to query parameters
                } else if (allowedTargetLabels && allowedTargetLabels.length === 0) {
                     // If the rule is an empty array, expand no nodes
                     console.log(`为类型 "${labelType}" 应用扩展规则：不展开任何节点。`);
                     // Can return empty results directly or skip the query
                     // Here we choose to skip the query and return empty data
                     const { nodes: newNodes, edges: newEdges } = { nodes: [], edges: [] };
                     // (Logic to handle empty data will execute later)
        
                     // --- Jump directly to data processing ---
                     // Ensure no attempt to add undefined data
                     // currentDataSet.nodes.add([]); // Add empty array - this might cause issues depending on vis.js version
                     // currentDataSet.edges.add([]); // Add empty array
                     // It's safer to just let the filtering logic handle empty newNodes/newEdges
                     toggleLoading(false); // Ensure loading indicator stops
                     return; // End the function early
        
                } else {
                    // If no rule is defined for this type, expand all neighbor types (default behavior)
                    console.log(`类型 "${labelType}" 没有指定扩展规则，将展开所有类型的邻居。`);
                    cypherQuery = `
                        MATCH (center:${labelType} {${propertyName}: $value})
                        OPTIONAL MATCH (center)-[rel]-(node)
                        RETURN center, rel, node
                        LIMIT 30 // Limit the number of results
                    `;
                    // queryParameters only needs 'value'
                }
        
                // --- 4. Execute the Query ---
                const result = await session.run(cypherQuery, queryParameters);
        
                // --- 5. Process Results and Update Graph ---
                // Assuming transformNeo4jData exists and correctly processes the result
                // It should handle cases where 'rel' or 'node' might be null from OPTIONAL MATCH
                const { nodes: newNodes, edges: newEdges } = transformNeo4jData(result, parentPosition);
                 // ✅ 插入这里，处理已存在的邻居节点
                for (const n of newNodes) {
                    const alreadyExists = currentDataSet.nodes.get(n.id);
                    if (alreadyExists && n.id !== nodeId) {
                        currentDataSet.nodes.update({ id: n.id, expanded: true });
                        console.log(`✅ 标记已存在邻居节点为 expanded: true => ${n.id}`);
                    }
                }           
                
        
                // Calculate positions (maintain previous uniform distribution logic)
                const childCount = newNodes.filter(n => n.id !== nodeId).length; // Filter out the center node itself
                const radius = 150;
                const angleStep = childCount > 0 ? (2 * Math.PI) / childCount : 0; // Avoid division by zero
                
                const mergedNodes = newNodes.map((n, index) => {
                if (n.id === nodeId) {
                    console.log(`Center node expanded: ${n.id}`);
                    return {
                        ...n,
                        ...(existingPositions[n.id] && { x: existingPositions[n.id].x, y: existingPositions[n.id].y })
                    };
                }
                
                const isExpandedNode = currentDataSet.nodes.get(n.id)?.expanded;
                if (isExpandedNode) {
                    console.log(`Already expanded node: ${n.id}`);
                    return {
                        ...n,
                        expanded: true,
                        ...(existingPositions[n.id] && { x: existingPositions[n.id].x, y: existingPositions[n.id].y })
                    };
                }
                
                console.log(`Expanding new node: ${n.id}`);
                const childIndex = newNodes.filter(nn => nn.id !== nodeId).findIndex(cn => cn.id === n.id);
                const angle = childIndex * angleStep;
                return {
                    ...n,
                    x: parentPosition.x + radius * Math.cos(angle),
                    y: parentPosition.y + radius * Math.sin(angle),
                    expanded: false
                };
                });
        
        
        
                // Filter out nodes and edges that already exist in the dataset
                const filteredNodes = mergedNodes.filter(n => !currentDataSet.nodes.get(n.id));
                const filteredEdges = newEdges.filter(e => !currentDataSet.edges.get(e.id));
        
                if (filteredNodes.length === 0 && filteredEdges.length === 0) {
                     console.log(`节点 ${nodeId} (${labelType}) 没有新的可扩展邻居（根据规则或已存在）。`);
                     // Optionally provide user feedback
                     // showError('没有找到新的相关节点。'); // Or use a gentler notification
                } else {
                     currentDataSet.nodes.add(filteredNodes);
                     currentDataSet.edges.add(filteredEdges);
                     
                     // Optimize physics layout parameters (can keep or adjust)
                     network.setOptions({
                         physics: { /* ... keep previous settings ... */
                            // Example settings (adjust as needed):
                            // enabled: true,
                            // barnesHut: {
                            //     gravitationalConstant: -3000,
                            //     centralGravity: 0.1,
                            //     springLength: 150,
                            //     springConstant: 0.05,
                            //     damping: 0.09,
                            //     avoidOverlap: 0.1
                            // },
                            // stabilization: {
                            //     iterations: 1000
                            // }
                         }
                     });
                     network.storePositions(); // Store current positions before simulation
                     network.startSimulation(); // Start simulation for new nodes/edges
                     // Consider stabilization or a timed stopSimulation if needed
                     // setTimeout(() => network.stopSimulation(), 5000); // Example: stop after 5 seconds
                }
        
        
            } finally {
                await session.close();
            }
    
    } catch (error) {
        // Log the detailed error for debugging
        console.error('节点展开失败:', error);
        // Show a user-friendly message
        showError(`节点展开失败: ${error.message}`);
    } finally {
        if (driver) {
            await driver.close();
        }
        toggleLoading(false); // Ensure loading indicator is turned off
    }
}


async function closeNodeRelationships() {
    if (!focusedNodeId) {
        console.error('❌ No node is currently focused');
        return;
    }

    try {
        const node = currentDataSet.nodes.get(focusedNodeId);
        if (!node) {
            console.error('❌ Focused node does not exist in the dataset');
            return;
        }
    
        const driver = neo4j.driver(
            neourl,
            neo4j.auth.basic(neouser, neopassword),
            { encrypted: 'ENCRYPTION_ON' }
        );
    
        const session = driver.session();
    
        const result = await session.run(`
            MATCH (n)-[r]-(connected)
            WHERE id(n) = $nodeId
            RETURN r, connected
        `, { nodeId: Number(focusedNodeId) });
    
        const edgesToRemove = [];
        const nodesToRemove = [];
    
        result.records.forEach(record => {
            const relationship = record.get('r');
            const connectedNode = record.get('connected');
    
            if (relationship) {
                const connectedNodeId = connectedNode.identity.toString();
                const connectedNodeInVis = currentDataSet.nodes.get(connectedNodeId);
                const isConnectedNodeExpanded = connectedNodeInVis?.expanded;
    
                // If the connected node is not expanded or the relationship is not between the focused node and an expanded node
                if (!isConnectedNodeExpanded && connectedNodeId !== focusedNodeId) {
                    console.log(`🔗 Deleting relationship: ${relationship.identity.toString()}`);
                    edgesToRemove.push(relationship.identity.toString());
                }
            }
    
            if (connectedNode) {
                const nodeId = connectedNode.identity.toString();
                const nodeInVis = currentDataSet.nodes.get(nodeId);
                const isExpanded = nodeInVis?.expanded;
    
                if (!isExpanded && nodeId !== focusedNodeId) { // Only remove nodes that are not expanded and are not the focused node
                    console.log(`🧼 Deleting connected node: ${nodeId} (expanded = ${isExpanded})`);
                    nodesToRemove.push(nodeId);
                } else {
                    console.log(`✅ Keeping expanded node: ${nodeId} (expanded = ${isExpanded})`);
                }
            }
        });
    
        // Do the removals of connected nodes and edges
        currentDataSet.nodes.remove(nodesToRemove);
        currentDataSet.edges.remove(edgesToRemove);
    
        network.setData(currentDataSet);
        hideContextMenu();
    
        console.log(`✅ Removed related nodes and their relationships`);
        console.log('🧾 Removed node IDs:', nodesToRemove);
        console.log('🧾 Removed edge IDs:', edgesToRemove);
    
        await session.close();
        await driver.close();
    
    } catch (error) {
        console.error('❌ Error in closing node relationships:', error);
    }
}


const nodeCache = new Map();

async function fetchDataFromNeo4j(label, limit = 1000) {
    // 检查缓存
    if (nodeCache.has(label)) {
        return nodeCache.get(label);
    }

    toggleLoading(true);
    
    try {
        const driver = neo4j.driver(
            neourl,
            neo4j.auth.basic(neouser, neopassword),
            { encrypted: 'ENCRYPTION_ON' }
        );
        const session = driver.session();
        const query = `
            MATCH (node:${label})
            RETURN node as center
            LIMIT ${limit}
        `;
        
        const result = await session.run(query);
        const { nodes } = transformNodesOnly(result);
        
        // 缓存结果
        nodeCache.set(label, nodes);
        
        await session.close();
        await driver.close();
        return nodes;
    } catch (error) {
        showError(`获取${label}数据失败: ${error.message}`);
        return [];
    } finally {
        toggleLoading(false);
    }
}

async function loadNodesByCriteria(criteria) {
    // 检查参数有效性
    if (!criteria || Object.keys(criteria).length === 0) {
        showError('请提供有效的搜索条件');
        return Promise.reject('无效的搜索条件');
    }
    
    toggleLoading(true);
    currentDataSet.nodes.clear();
    currentDataSet.edges.clear();
    
    try {
        const driver = neo4j.driver(
            neourl,
            neo4j.auth.basic(neouser, neopassword),
            { encrypted: 'ENCRYPTION_ON' }
        );
        
        const session = driver.session();
        
        // 构建OPTIONAL MATCH子句和参数
        let optionalMatches = [];
        let params = {};
        let index = 0;
        
        for (const [nodeType, nodeName] of Object.entries(criteria)) {
            const paramName = `value${index}`;
            optionalMatches.push(`OPTIONAL MATCH (n${index}:${nodeType} {名称: $${paramName}})`);
            params[paramName] = nodeName;
            index++;
        }
        
        // 构建返回结果的UNWIND结构
        const returnNodes = Array.from({length: index}, (_, i) => `n${i}`);
        const unwindItems = returnNodes.map(n => `{center: ${n}, rel: null, node: null}`);
        
        // 完整的Cypher查询
        const cypher = `
            ${optionalMatches.join('\n')}
            WITH ${returnNodes.join(', ')}
            UNWIND [${unwindItems.join(', ')}] AS result
            RETURN result.center AS center, result.rel AS rel, result.node AS node
        `;
        
        console.log('执行查询:', cypher, params);
        
        const result = await session.run(cypher, params);
        
        console.log('查询结果:', result);
        
        const { nodes, edges } = transformNeo4jData(result);
        currentDataSet.nodes.update(nodes);
        currentDataSet.edges.update(edges);
        
        if (network) {
            network.setData(currentDataSet);
        } else {
            createVisNetwork();
        }
        
        await session.close();
        await driver.close();
        
        if (nodes.length === 0) {
            showError('未找到匹配的节点');
        }
        
        toggleLoading(false);
        return Promise.resolve(nodes);
        
    } catch (error) {
        showError('查询失败: ' + error.message);
        console.error('查询执行错误:', error);
        toggleLoading(false);
        return Promise.reject(error);
    }
}
function getNodeQueryParams(nodeType) {
    const typeMapping = {
    
        '作品': ['作品', 'artwork_id'], // 作品节点应该用 artwork_id 来匹配
        '人物': ['人物', '名称'],
        '纹样': ['纹样', '名称'],         // 假设 '纹样' 节点用 'name' 或 '名称' 属性，请根据你的数据库确认
        '文化内涵': ['文化内涵', '名称'],  
        '工艺': ['工艺', '名称'],
        '朝代时期': ['朝代时期', '名称'],  
        '功能': ['功能', '名称'],
        '器型': ['器型', '名称'],
        '材质': ['材质', '名称'],
        '流派': ['流派', '名称'],
        '技法': ['技法', '名称'] 
        // --- 根据你的实际数据模型添加或修改其他标签和属性 ---
    };
    
    const mapping = typeMapping[nodeType];
    if (!mapping) {
        console.error(`未找到节点类型 "${nodeType}" 的查询参数映射。`);
        // 返回 null 或抛出错误，而不是返回可能无效的默认值
        return null;
        // 或者 return ['Unknown', 'invalid_property']; // 让后续检查更容易发现问题
    }
    return mapping;
}


function transformNeo4jData(result, parentPosition = {x: 0, y: 0}) {
    const nodesMap = new Map();
    const edgesMap = new Map();
    
    result.records.forEach(record => {
    try {
        [record.get('center'), record.get('node')].forEach(node => {
            if (!node) return;
            const nodeId = node.identity.toString();
            if (nodesMap.has(nodeId)) return;
    
            const properties = node.properties || {};
            const group = (node.labels || [])[0] || 'Unknown';
            const baseColor = getNodeColor([group]);  // 确保每个节点一开始就有颜色
            
            // 截断过长的标签文字（最多8个字符）
            const rawLabel = properties.名称 || properties.name || `节点 ${nodeId}`;
            const truncatedLabel = rawLabel.length > 8 ? 
                rawLabel.substring(0, 7) + '...' : 
                rawLabel;
                // --- Create Base Node Object ---
                    const nodeData = {
                        id: nodeId,
                        label: truncatedLabel,
                        group: group,
                        expanded: false,  // 认标记为未展开
                        color: {
                            background: baseColor,
                            border: baseColor,
                            highlight: { // Consistent highlight colors
                                background: baseColor,
                                border: '#FFD700' // Gold border on highlight
                            }
                        },
                        shape: 'circle',
                        size: 60, // Slightly smaller default size
                        font: {
                            size: 12,
                            color: '#ffffff',
                            strokeWidth: 2,
                            strokeColor: '#000000',
                            multi: false
                        },
                        borderWidth: 2,
                        margin: 6,
                        widthConstraint: { 
                            minimum: 30,
                            maximum: 30
                        },
                        // Assign initial position near parent or randomly
                        x: parentPosition.x + (Math.random() - 0.5) * 100,
                        y: parentPosition.y + (Math.random() - 0.5) * 100,
                        // Store all properties for potential use (like artwork_id)
                        // properties: properties // Optionally store all properties if needed elsewhere
                    };
    
                    // --- MODIFICATION START: Explicitly add artwork_id if present ---
                    if (properties.artwork_id) {
                        nodeData.artwork_id = properties.artwork_id;
                    }
                    if (properties.ysyk_artist_id) {
                        nodeData.ysyk_artist_id = properties.ysyk_artist_id;
                    }
                    if (group === '作品') {
                        nodeData.size = 30; // Reduce the size of nodes with images
                        // *** IMPORTANT: Replace 'properties.image_url' with the actual property name
                        // *** from your Neo4j data that holds the image URL.
                        const imageUrl = properties.thumbnail; // Use actual property or a placeholder/default image URL
    
                        nodeData.shape = 'circularImage'; // Change shape to display image in a circle
                        nodeData.image = imageUrl;        // Set the image URL
                        // Optional: Adjust size specifically for image nodes if needed
                        // nodeData.size = 40;
                        // The background color won't be visible, but the border will be.
                        nodeData.color.border = baseColor; // Ensure border matches group color
                        nodeData.color.highlight.border = '#FFD700'; // Highlight border
                    }                        
                    
                    // --- MODIFICATION END ---
    
                    nodesMap.set(nodeId, nodeData);                    
                
                
                
    
        });
    
        const rel = record.get('rel');
        if (rel) {
            const relId = rel.identity.toString();
            if (!edgesMap.has(relId)) {
                edgesMap.set(relId, {
                    id: relId,
                    from: rel.start.toString(),
                    to: rel.end.toString(),
                    label: rel.type || '',  // 显示关系类型
                    color: getEdgeColor(rel.type),  // 根据关系类型设置边的颜色
                    width: 1,  // 边更细巧
                    smooth: { 
                        type: 'continuous',  // 强制使用直线
                        roundness: 0  // 直线，不加弯曲
                    },
                    arrows: {
                        to: {
                          enabled: true,
                          scaleFactor: 0.8    // 箭头大小缩放
                        }
                      },
                    font: {  // 显示边的文字
                        size: 10,
                        color: '#ffffff',
                        align: 'bottom'
                    }
                });
            }
        }
    } catch (e) {
        console.error('数据处理错误:', e);
    }
    });
    
    return {
    nodes: Array.from(nodesMap.values()),
    edges: Array.from(edgesMap.values())
    };
}


function createVisNetwork() {
    const container = document.getElementById('graph');
    const options = {
        nodes: {
            size: 60,  // 全局统一大小
            shadow: false,  // 禁用阴影
            font: {
                size: 14,
                color: '#2c3e50',
                strokeWidth: 2,
                strokeColor: '#ffffff',
                multi: true
            },
            shape: 'circle',
            borderWidth: 2,
            physics: true,
            widthConstraint: {
                minimum: 60,
                maximum: 60
            },
            scaling: {
                min: 60,
                max: 60  // 禁用缩放变化
            },
            chosen: {
                label: false, // 标签在高亮时是否变化
                node: function(values, id, selected, hovering) {
                    // 获取原始节点数据以保持颜色不变
                    const nodeData = currentDataSet.nodes.get(id);
                    if (nodeData && nodeData.color) {
                       // 强制设置高亮时的背景色和边框色为原始颜色
                       values.color = nodeData.color.background;
                       values.borderColor = nodeData.color.border;
                    }
                    // 不在此处处理 shadow，由 hover/blur 事件单独处理
                }
            }
        },
        edges: {
            width: 1,
            smooth: { 
                type: 'continuous',  // 强制使用直线
                roundness: 0  // 直线，不加弯曲
            },
            font: {  // 新增全局边文字配置
                size: 10,
                color: '#ffffff',  // 设置为纯白色
                strokeWidth: 0,  // 去除边文字的阴影宽度
                strokeColor: 'rgba(0,0,0,0)',  // 去除阴影颜色
                align: 'bottom',
                background: 'rgba(0, 0, 0, 0)'  // 去除文字背景
            },
            labelHighlightBold: false,
            selectionWidth: 1,
            arrowStrikethrough: false,
            chosen: {
                edge: function(values, id, selected, hovering) {
                    if (selected || hovering) {
                        values.width = 1;
                        values.color = '#FFA500';
                    }
                    return values;
                }
            }
        },
        physics: {
            stabilization: {
                enabled: true,
                iterations: 100,
                updateInterval: 50
            },
            barnesHut: {
                gravitationalConstant: -3000, // Adjusted gravity slightly
                springLength: 180,         // Adjusted spring length
                springConstant: 0.03,       // Adjusted spring constant
                damping: 0.3,              // Adjusted damping
                avoidOverlap: 1             // Stronger overlap avoidance
            },
             solver: 'barnesHut' // Explicitly state the solver
        },
        interaction: {
            hover: true,
            hoverConnectedEdges: false,
            tooltipDelay: 200,
            multiselect: false,
            dragView: true,
            dragNodes: true
        },
        layout: {
            improvedLayout: true 
        }
    };

    if (!network) {
        network = new vis.Network(container, currentDataSet, options);
        
        network.on("click", async (params) => {
            if (isClickHandled) {
                isClickHandled = false;  // Reset flag for next click
                return;
            } 
            // Prevent the click event from executing too quickly by adding a small delay
            if (clickTimeout) {
                clearTimeout(clickTimeout);
            }     

        });

        network.on("doubleClick", async (params) => {
            if (clickTimeout) {
                clearTimeout(clickTimeout);
            }
           
            isClickHandled = true;
        
            if (params.nodes.length > 0) {
                const nodeId = params.nodes[0];
                console.log(`🟢 双击节点 ${nodeId}`);
                selectedNodeId = nodeId;
                const node = currentDataSet.nodes.get(nodeId);
                console.log(`🟢 节点信息：`,node);
                if (!node) return;
                
                if (!node.expanded) {
                    console.log(`🟢 双击展开节点 ${nodeId}`);
                    await expandNode(nodeId);
                } else {
/*                            console.log(`🔴 双击关闭节点 ${nodeId}`);
                    focusedNodeId = nodeId; // 👈 设为当前聚焦
                    await closeNodeRelationships();*/
                }
            }
        });
        network.on("oncontext", function (params) {
            if (params.nodes.length > 0) {
                selectedNodeId = params.nodes[0];
                focusedNodeId = params.nodes[0];  
                const node = currentDataSet.nodes.get(selectedNodeId);
                if (node) {
                    // 获取动态生成的菜单项
                    const menuHtml = generateMenuItems(node);
                    
                    // 动态设置菜单内容
                    const menu = document.getElementById('contextMenu');
                    menu.innerHTML = menuHtml;
                }                          
                
                
                
                showContextMenu(params.pointer.DOM);
            }
        });
        // 新增光环特效事件监听
        network.on("hoverNode", (params) => {
            focusedNodeId = params.node;
             // Clear any existing timeout if the node gains focus again before 1 second
            if (menuTimeout) {
                clearTimeout(menuTimeout);
            }                   
             // Start a timeout to display the context menu after 1 second
            menuTimeout = setTimeout(() => {
                const node = currentDataSet.nodes.get(params.node);
                if (node) {
                     const menuHtml = generateMenuItems(node);
                    
                    // 动态设置菜单内容
                    const menu = document.getElementById('contextMenu');
                    menu.innerHTML = menuHtml;                           
                    
                    
                    const position = params.pointer.DOM;
                    
                    if(menuHtml!=''){
                        showContextMenu(position);
                        isMenuVisible = true;
                    }
   
                }
                if(node.group=='作品'){
                     showArtworkPopup(node.artwork_id)
                }
            }, 2000);  // 1000 ms = 1 second                   
            if (currentHaloNodeId !== params.node) {
                stopHaloEffect();
                startHaloEffect(params.node);
            }
        });
        network.on("blurNode", () => {
            focusedNodeId = null;
            // Hide the context menu when the node loses focus
            if (isMenuVisible) {
                hideContextMenu();
                isMenuVisible = false;
            }
        
            // Clear the timeout to prevent showing the menu if the node is blurred before 1 second
            if (menuTimeout) {
                clearTimeout(menuTimeout);
            }
        });
        network.on("blurNode", () => {
            stopHaloEffect();
        });

    } else {
        network.setOptions(options);
    }
}

function startHaloEffect(nodeId) {
    stopHaloEffect(); // 先清除之前的效果

    currentHaloNodeId = nodeId;
    const node = currentDataSet.nodes.get(nodeId);
    if (!node) return;

    // Get the original node color for potential future use, though not needed here
    // const baseColor = getNodeColor([node.group]);

    // Apply a static shadow effect for the halo
    currentDataSet.nodes.update({
        id: nodeId,
        // borderColor: baseColor, // Ensure border color DOES NOT change
        shadow: {
            enabled: true,
            // Use a color similar to the image's halo (e.g., light blue/cyan, semi-transparent)
            color: 'rgba(255, 215, 0, 0.8)', // Example: Cornflower blue glow
            size: 30, // Adjust size of the halo
            x: 0,
            y: 0
        }
    });


}

function stopHaloEffect() {
    if (currentHaloNodeId) {
        const node = currentDataSet.nodes.get(currentHaloNodeId); // 获取当前节点的数据

        if (node && node.color) { // 确保节点存在并且有颜色信息
             currentDataSet.nodes.update({
                id: currentHaloNodeId,
                shadow: { // 关闭阴影
                    enabled: false
                },
                // --- 新增：显式恢复节点的原始颜色 ---
                color: {
                    background: node.color.background, // 使用节点数据中存储的原始背景色
                    border: node.color.border,         // 使用节点数据中存储的原始边框色
                    highlight: { // 同时也恢复 highlight 的设置（虽然理论上用不到，但保持一致性）
                       background: node.color.background,
                       border: node.color.border
                    }
                }
                // --- 颜色恢复结束 ---
            });
        }
        currentHaloNodeId = null; // 重置当前光环节点 ID
    }
}
function generateNodeUrl(node) {
    const base = 'https://www.hongmengyishu.com';
    const group = node.group.toLowerCase();
    const label = encodeURIComponent((node.label || '').trim());
    console.log(node)
    
    switch(group) {
        case '作品':
            return `${base}/portal/article/index/id/${node.artwork_id}.html`;
        case 'museum':
            return `${base}/portal/museum?museumid=${node.id}`;
        case 'age':
            return `${base}/?times=${label}`;
        default:
            return `${base}/404.html`;
    }
}
function showContextMenu(position) {
    const menu = document.getElementById('contextMenu');
    menu.style.left = position.x + 'px';
    menu.style.top = position.y + 'px';
    menu.style.display = 'block';
}
function hideContextMenu() {
    const menu = document.getElementById('contextMenu');
    menu.style.display = 'none';
}







function getNodeColor(labels = []) {
    const colorMap = {
        '作品': '#3498db',          // 蓝色
        '纹样': '#e74c3c',          // 红色
        '文化内涵': '#2ecc71',      // 绿色
        '工艺': '#9b59b6',          // 紫色
        '朝代时期': '#f39c12',      // 橙色
        '功能': '#1abc9c',          // 青色
        '器型': '#f1c40f',          // 黄色
        '材质': '#34495e',          // 深蓝色
        '技法': '#e67e22'           // 橙红色
    };
    return colorMap[labels[0]] || '#95A5A6';  // 默认颜色为灰色
}


function getEdgeColor(type) {
    return type === 'BELONGS_TO' ? '#50E3C2' : '#9B9B9B';
}

function toggleLoading(loading) {
    isLoading = loading;
}

function showError(message) {
    alert(message);
    console.error(message);
}

window.onload = async function() {
    const success = await getInfo();
    if (success){
        const urlParams = new URLSearchParams(window.location.search);
        const id = urlParams.get('id');
        if (id) {
            loadAndRenderWork(id);
        }else{
             const selector = document.getElementById('nodeTypeSelector');
            if (selector) {
                selector.value = '技法';  // Set the value to '技法'
            }               
            toggleLoading(true);
            currentDataSet.nodes.clear();
            currentDataSet.edges.clear();
        
            
            try {
                const nodes = await fetchDataFromNeo4j('技法');
                const limitedNodes = nodes.slice(0, 1000);
                
                // 直接显示选中的节点（不再需要隐藏/显示切换逻辑）
                currentDataSet.nodes.add(limitedNodes);
                if (network) {
                    network.setData(currentDataSet);  // Ensure network data is updated
                } else {
                    createVisNetwork();  // If network is not initialized, initialize it
                }
                
                // 自动聚焦到节点集
                if (network && limitedNodes.length > 0) {
                    network.focus(limitedNodes[0].id, {
                        animation: { duration: 500, easingFunction: 'easeInOutQuad' }
                    });
                }
                
            } catch (error) {
                showError(`加载技法节点失败: ${error.message}`);
            } finally {
                toggleLoading(false);
            }
        }               
    }else{
        showError(`获取关键信息失败`);
    }

}       
    
// 生成动态菜单项的函数
function generateMenuItems(node) {
    let menuHtml = '';
    console.log('node.group:'+node.group)
    // 根据节点类型动态生成不同的菜单项
    if (node.group === '人物' && node.ysyk_artist_id != null) {
        
        menuHtml += `<li onclick="jumptoArtist('${node.label}')">浏览作者作品</li>`;
    }
    if(menuHtml==''){
        
    }
    
    
    return menuHtml;
}
// 跳转到作品页面
function jumptoArtwork(artwork_id) {
    const url = `https://www.hongmengyishu.com/portal/article/index/id/${artwork_id}.html`;
    window.open(url, '_blank');  // 在新标签页打开
}

// 跳转到作者的页面
function jumptoArtist(name) {
    const url = `https://www.hongmengyishu.com/portal/artist_article_list/index/author/${name}`;
    window.open(url, '_blank');  // 在新标签页打开
}         

let artworkNodesVisible = true;  // 默认作品节点是可见的

function toggleArtworkVisibility() {
    const allNodes = currentDataSet.nodes.get();  // 获取所有节点
    const artworkNodes = allNodes.filter(node => node.group === '作品'); // 找到所有作品节点
    
    if (artworkNodes.length === 0) {
        // 如果没有作品节点，给出提示
        showError("当前没有作品节点");
        return; // 退出函数，不执行后续操作
    }
    const updatedNodes = allNodes.map(node => {
        if (node.group === '作品') {
            return {
                ...node,
                hidden: artworkNodesVisible  // 切换 hidden 属性
            };
        }
        return node;
    });

    currentDataSet.nodes.update(updatedNodes); // 更新节点显示状态
    artworkNodesVisible = !artworkNodesVisible; // 反转状态
    // 更新按钮文本
    const toggleButton = document.getElementById('toggleArtworkVisibilityBtn');
    if (artworkNodesVisible) {
        toggleButton.textContent = '隐藏作品节点'; // 如果作品节点显示，按钮文本为 '隐藏作品节点'
    } else {
        toggleButton.textContent = '显示作品节点'; // 如果作品节点隐藏，按钮文本为 '显示作品节点'
    }
}
  
 // 用来跟踪每个节点的显示状态
let patternNodesVisible = false;

// 清空现有的节点和边
function clearExistingData() {
    currentDataSet.nodes.clear();  // 清空节点
    currentDataSet.edges.clear();  // 清空边
}
/**
 * 转换仅包含节点的Neo4j查询结果（优化名称显示版）
 * @param {neo4j.Result} result - Neo4j查询结果
 * @returns {{nodes: Array, edges: Array}} - 适配vis.js的数据格式
 */
function transformNodesOnly(result) {
    const nodes = result.records.map(record => {
        const node = record.get('center');
        
        // 优先使用 name 属性，其次尝试 title、label，最后用ID兜底
        const displayName = node.properties.名称;
        
        return {
            id: node.identity.toString(),
            label: displayName,
            group:node.labels[0],
            name: displayName,  // 确保有可读名称
            title: displayName, // 用于tooltip显示
            ...node.properties // 保留所有原始属性
        };
    });
    
    // 返回空edges数组保持数据结构一致
    return {
        nodes: nodes,
        edges: []
    };
}


// 切换纹样节点的显示状态，最多显示500个
function togglePatternVisibility() {
    clearExistingData();  // 清空现有的节点和边

    // 查询Neo4j中的“纹样”节点
    fetchDataFromNeo4j("纹样").then((nodes) => {
        // 限制最多显示500个纹样节点
        const limitedNodes = nodes.slice(0, 1000);
        currentDataSet.nodes.add(limitedNodes);  // 将纹样节点添加到网络中

        // 切换纹样节点的可见性
        const updatedNodes = limitedNodes.map(node => ({
            ...node,
            hidden: !patternNodesVisible  // 切换纹样节点的可见性
        }));

        currentDataSet.nodes.update(updatedNodes);  // 更新显示状态
        patternNodesVisible = !patternNodesVisible;  // 切换显示状态
    });
}
// 跟踪材质节点显示状态
let materialNodesVisible = false;

// 显示/隐藏材质节点
function toggleMaterialVisibility() {
    clearExistingData();
    fetchDataFromNeo4j("材质").then((nodes) => {
        const limitedNodes = nodes.slice(0, 1000);
        currentDataSet.nodes.add(limitedNodes);
        
        const updatedNodes = limitedNodes.map(node => ({
            ...node,
            hidden: !materialNodesVisible
        }));

        currentDataSet.nodes.update(updatedNodes);
        materialNodesVisible = !materialNodesVisible;
    });
}
// 跟踪技法节点显示状态
let techniqueNodesVisible = false;

// 显示/隐藏技法节点
function toggleTechniqueVisibility() {
    clearExistingData();
    fetchDataFromNeo4j("技法").then((nodes) => {
        const limitedNodes = nodes.slice(0, 1000);
        currentDataSet.nodes.add(limitedNodes);
        
        const updatedNodes = limitedNodes.map(node => ({
            ...node,
            hidden: !techniqueNodesVisible
        }));

        currentDataSet.nodes.update(updatedNodes);
        techniqueNodesVisible = !techniqueNodesVisible;
    });
}


// 全局状态跟踪
const nodeVisibilityStates = {
    '作品': true,
    '纹样': false,
    '材质': false,
    '技法': false,
    '工艺': false
};


async function loadSelectedNodes() {
    const selector = document.getElementById('nodeTypeSelector');
    const nodeType = selector.value;
    
    if (!nodeType) return; // 空选项不处理
    
    clearExistingData();
    toggleLoading(true);
    
    try {
        const nodes = await fetchDataFromNeo4j(nodeType);
        const limitedNodes = nodes.slice(0, 1000);
        
        // 直接显示选中的节点（不再需要隐藏/显示切换逻辑）
        currentDataSet.nodes.add(limitedNodes);
        
        // 自动聚焦到节点集
        if (network && limitedNodes.length > 0) {
            network.focus(limitedNodes[0].id, {
                animation: { duration: 500, easingFunction: 'easeInOutQuad' }
            });
        }
        
    } catch (error) {
        showError(`加载${nodeType}节点失败: ${error.message}`);
    } finally {
        toggleLoading(false);
    }
}
// 跟踪孤立节点显示状态
let isolatedNodesVisible = true;

// 隐藏/显示孤立节点
function toggleIsolatedNodes() {
    const allNodes = currentDataSet.nodes.get();
    const allEdges = currentDataSet.edges.get();
    
    // 找出孤立节点（没有连接的节点）
    const connectedNodeIds = new Set();
    allEdges.forEach(edge => {
        connectedNodeIds.add(edge.from);
        connectedNodeIds.add(edge.to);
    });
    
    const isolatedNodes = allNodes.filter(node => !connectedNodeIds.has(node.id));
    
    // 切换孤立节点的显示状态
    const updatedNodes = allNodes.map(node => {
        if (isolatedNodes.some(n => n.id === node.id)) {
            return {
                ...node,
                hidden: isolatedNodesVisible // 如果孤立节点当前可见，则隐藏它们
            };
        }
        return node;
    });
    
    currentDataSet.nodes.update(updatedNodes);
    isolatedNodesVisible = !isolatedNodesVisible;
    
    // 更新按钮文本
    document.getElementById('toggleIsolatedNodesBtn').textContent = 
        isolatedNodesVisible ? '隐藏孤立节点' : '显示孤立节点';
    
    // 如果网络存在，重新绘制
    if (network) {
        network.redraw();
    }
}



        
/**
 * 通过API获取艺术品数据并显示在layer弹窗中
 * @param {number} id - 艺术品ID
 */
function showArtworkPopup(id) {

  // 显示加载中状态
  const loadingIndex = layer.load(1, {
    shade: [0.5, '#000'] // 半透明黑色遮罩
  });

  // 获取数据
  fetchArtworkById(id)
    .then(artwork => {
      layer.close(loadingIndex); // 关闭加载中
      renderArtworkPopup(artwork); // 渲染弹窗
    })
    .catch(error => {
      layer.close(loadingIndex);
      layer.alert('加载艺术品数据失败，请稍后再试', {icon: 2});
      console.error('弹窗数据加载失败:', error);
    });
}

photos = null;
function renderArtworkPopup(artwork) {
  // 构建主图HTML
  const mainImage = artwork.more?.photos?.[0]?.originurl; // 第一张图片的URL
  photos = artwork.more?.photos;
  const imageurl = artwork.more?.photos?.[0]?.url; // 其他可能用到的图片URL

  // 打开弹窗
  var popupIndex = layer.open({
    type: 1,
    title: `${artwork.post_title} - ${artwork.post_author}`,
    area: ['80%', '80%'], // 响应式宽高
    maxmin: true, // 允许最大化/最小化
    content: `
      <div class="artwork-popup" style="background-color: #000; color: #fff;">
        <!-- 图片部分 - 显示第一张图片 -->
        <div class="artwork-image-fullwidth">
          <img src="${mainImage}" alt="${artwork.post_title}" 
            onclick="${artwork.bigimg == 0 ? 'viewAllImages()' : `bigimg({id: ${artwork.id}, url: '${imageurl}'})`}">
          ${artwork.more?.photos?.length > 1 ? `
            <div class="image-count">+${artwork.more.photos.length - 1}张</div>
          ` : ''}
        </div>
        
        <!-- 信息部分 -->
        <div class="artwork-details">
          <h3 style="color: #fff;">${artwork.post_title}</h3>
          <p class="artist" style="color: #fff;">${artwork.post_author}</p>
          
          <div class="meta-grid">
            <div class="meta-item" style="background-color: #222; color: #fff;">
              <span class="meta-label" style="color: #aaa;">年代</span>
              <span class="meta-value" style="color: #fff;">${artwork.post_age || '未知'}</span>
            </div>
            <div class="meta-item" style="background-color: #222; color: #fff;">
              <span class="meta-label" style="color: #aaa;">尺寸</span>
              <span class="meta-value" style="color: #fff;">${artwork.post_size || '未知'}</span>
            </div>
            <div class="meta-item" style="background-color: #222; color: #fff;">
              <span class="meta-label" style="color: #aaa;">收藏</span>
              <span class="meta-value" style="color: #fff;">${artwork.post_museum || '未知'}</span>
            </div>
          </div>
          
          <!-- 右侧垂直排列的按钮 -->
          <div class="detail-buttons-right">
            <button class="detail-button" 
                    onclick="window.open('https://www.hongmengyishu.com/portal/article/index/id/${artwork.id}', '_blank')">
              作品详情
            </button>
            ${artwork.bigimg == 1 ? `
            <button class="detail-button" 
                    onclick="bigimg({id: ${artwork.id}, url: '${imageurl}'})">
              高清大图
            </button>
            ` : ''}
          </div>     
      </div>
    `,
    btn: [],
    shadeClose: true,
    success: function(layero) {
      // 弹窗打开后执行
      layero.find('.desc-content').scrollTop(0);
      
      // 添加失去焦点自动关闭功能
      var $layer = $('#' + layero.attr('id'));
      $layer.on('blur', function() {
        layer.close(popupIndex);
      });
      
      // 兼容移动端触摸事件
      $layer.on('touchstart', function(e) {
        if (!$(e.target).closest('.layui-layer-content').length) {
          layer.close(popupIndex);
        }
      });
    },
    cancel: function() {
      // 清除事件监听
      $(document).off('click.autoCloseLayer');
    }
  });
  
  // 全局点击事件，点击弹窗外部时关闭
  $(document).on('click.autoCloseLayer', function(e) {
    if (!$(e.target).closest('.layui-layer').length && 
        !$(e.target).hasClass('layui-layer-shade')) {
      layer.close(popupIndex);
    }
  });
}

function viewAllImages() {
  // 保持你原有的图片处理逻辑
  console.log(photos);
  var viewerContainer = document.createElement('div');
  viewerContainer.id = 'imageGalleryContainer';
  document.body.appendChild(viewerContainer);

  // 保持你原有的图片添加方式
  photos.forEach(function(photo) {
    var imgElement = document.createElement('img');
    imgElement.src = photo.url; // 保持你的缩略图逻辑
    imgElement.setAttribute('data-original', photo.originurl); // 保持你的原图逻辑
    viewerContainer.appendChild(imgElement);
  });

  // 仅添加 Viewer 的 z-index 控制
  const viewer = new Viewer(viewerContainer, {
    url: 'data-original',
    title: true,
    fullscreen: true,
    zoomable: true,
    rotatable: true,
    // 新增的关键配置：强制置顶
    shown() {
      const viewerElements = document.querySelectorAll('.viewer-container, .viewer-backdrop');
      viewerElements.forEach(el => {
        el.style.zIndex = '2147483647'; // 最大值
      });
    }
  });

  // 保持你原有的显示逻辑
  viewer.show();
}

/**
 * 获取艺术品数据的底层函数
 */
function fetchArtworkById(id) {
  return fetch(`https://www.hongmengyishu.com/api/portal/articles/${id}`)
    .then(response => {
      if (!response.ok) throw new Error('网络响应异常');
      return response.json();
    })
    .then(data => {
      if (data.code !== 1) throw new Error(data.msg || 'API返回错误');
      return data.data;
    });
}

function bigimg(bigimg)
{


  url = "/portal/image/index?image="+bigimg.url + "&"+"articleId="+bigimg.id

  if (url.indexOf("故宫博物院M")==-1)
  {
    url =url.replace("压缩图","高清")  
  }
//	fullScreen();
 //window.location.href=url;

/*	var height = window.screen.height-250;
	var width = window.screen.width-250;*/
	
	var height = window.innerHeight;
	var width = window.innerWidth;	
	
	width = width + 'px'
	height = height+'px'

   var index = layer.open({
      type: 2,
      //title: '艺术云库高清展示',
      title:false,
      scrollbar:false,
      maxmin: true,
      shade: [0.8, '#393D49'],
      shadeClose:true,
      anim: 2,
      area: [width,height],
      content: url ,
      end: function(){
      //  fullScreen();
      },

    });
  //layer.full(index)
}        


function analysisImage() {
    console.log('开始分析图片...');
    var refImgUrl = $(".ref_image_url").val();
    
    if (!refImgUrl) {
        console.error('未提供图片URL');
        alert('请先选择或上传图片');
        return;
    }
     // 显示图片元素
    $("#ref_image_url").show();       
    // 构建完整图片URL
    var image_url = "https://www." + window.location.hostname.split('.').slice(-2).join('.') + '/upload/' + refImgUrl;
    console.log('图片URL:', image_url);
     // 设置图片src
    $("#ref_image_url").attr("src", image_url);       
    // 构建API请求URL
    var apiUrl = '/portal/visual/analysis?image=' + encodeURIComponent(image_url);
    console.log('API请求URL:', apiUrl);

    // 发起AJAX请求
    $.ajax({
        url: apiUrl,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('API响应:', response);
            
            if (response.status === 'success') {
                // 打印返回的data
                console.log('分析结果:', response.data);
                
                // 首先通过现有函数加载相关节点
                loadNodesByCriteria(response.data).then(() => {
                    // 在现有节点加载完成后创建表示分析图片的虚拟节点
                    createVirtualImageNode(image_url, response.data);
                });
            } else {
                console.error('API返回错误:', response.message);
                alert('分析失败: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            var errorMsg = '请求失败: ' + (xhr.responseJSON?.message || error);
            console.error('API请求错误:', errorMsg, xhr);
            alert(errorMsg);
        }
    });
}


// 创建表示分析图片的虚拟节点并连接到找到的节点
function createVirtualImageNode(imageUrl, analysisData) {
    // 确保网络已经初始化
    if (!network) {
        console.error('网络未初始化，无法创建虚拟节点');
        return;
    }
    
    // 生成唯一ID (时间戳+随机数)
    const virtualNodeId = 'virtual_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
    
    // 创建表示上传图片的虚拟节点
    const virtualNode = {
        id: virtualNodeId,
        label: '分析图片',
        group: '分析图片', // 自定义组
        shape: 'circularImage',
        image: imageUrl,
        size: 50, // 稍大一些的尺寸，突出显示
        color: {
            background: '#4CAF50', // 绿色背景
            border: '#2E7D32',     // 深绿色边框
            highlight: {
                background: '#4CAF50',
                border: '#FFD700'  // 高亮时金色边框
            }
        },
        borderWidth: 3,
        font: {
            size: 14,
            color: '#ffffff',
            strokeWidth: 2,
            strokeColor: '#000000'
        },
        // 在网络中心位置创建节点
        x: 0,
        y: 0,
        fixed: false // 允许物理引擎移动节点
    };
    
    // 查找当前图中与分析结果匹配的节点
    const allNodes = currentDataSet.nodes.get();
    const connectedNodeIds = [];
    
    // 收集所有匹配的节点ID
    allNodes.forEach(node => {
        // 判断该节点是否匹配分析结果中的任一条件
        const nodeLabel = node.group;
        const nodeName = node.label;
        
        // 检查节点是否与分析结果中的任一类型匹配
        for (const [type, name] of Object.entries(analysisData)) {
            if (nodeLabel === type && nodeName === name) {
                connectedNodeIds.push(node.id);
                break;
            }
        }
    });
    
    // 添加虚拟节点到数据集
    currentDataSet.nodes.add(virtualNode);
    
    // 创建连接虚拟节点和匹配节点的边
    const newEdges = connectedNodeIds.map(targetId => ({
        id: `edge_${virtualNodeId}_to_${targetId}`,
        from: virtualNodeId,
        to: targetId,
        label: '分析结果',
        color: '#4CAF50', // 绿色边
        width: 2,
        dashes: [5, 5], // 虚线表示非数据库关系
        arrows: {
            to: {
                enabled: true,
                scaleFactor: 0.8
            }
        },
        font: {
            size: 10,
            color: '#4CAF50',
            align: 'middle'
        }
    }));
    
    // 添加新边到数据集
    if (newEdges.length > 0) {
        currentDataSet.edges.add(newEdges);
        
        // 重新应用物理模拟以调整布局
        network.setOptions({
            physics: {
                enabled: true,
                stabilization: {
                    iterations: 100
                }
            }
        });
        
        // 让网络重新稳定后聚焦到新节点
        setTimeout(() => {
            network.focus(virtualNodeId, {
                scale: 0.8,
                animation: {
                    duration: 1000,
                    easingFunction: 'easeInOutQuad'
                }
            });
            
            // 5秒后停止物理模拟
            setTimeout(() => {
                network.setOptions({ physics: { enabled: false } });
            }, 5000);
        }, 100);
        
        } else {
        // 如果没有找到匹配的节点
        layer.msg('未找到与分析结果匹配的现有节点', {
            icon: 0,
            time: 3000
        });
    }
}




$('#imageGallery').on('click', function() {
    if (typeof $.fn.lightGallery !== 'undefined') {
        $(this).lightGallery({
            selector: '.lslide'
        });
    } else {
        console.error("lightGallery 未加载。请确保该库可用。");
    }
});


