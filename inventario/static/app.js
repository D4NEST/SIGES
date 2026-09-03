// ====================================================================
// CONFIGURACIÓN SEGURA - USAR MISMO DOMINIO
// ====================================================================
const API_BASE_URL = window.location.origin;
const AUTH_URL = `${API_BASE_URL}/api/auth`;
const INVENTARIO_URL = `${API_BASE_URL}/api/inventario`;

console.log("🚀 Sistema de inventario inicializando...");
console.log("🌐 API Base URL:", API_BASE_URL);

// ====================================================================
// ELEMENTOS DEL DOM - ACTUALIZADOS Y VERIFICADOS
// ====================================================================
const loginScreen = document.getElementById("loginScreen");
const dashboardScreen = document.getElementById("dashboardScreen");
const loginForm = document.getElementById("loginForm");
const loginError = document.getElementById("loginError");
const logoutBtn = document.getElementById("logoutBtn");
const userName = document.getElementById("userName");
const userInitial = document.getElementById("userInitial");

const inventoryTableBody = document.getElementById("inventoryTableBody");
const totalItems = document.getElementById("totalItems");
const lowStockItems = document.getElementById("lowStockItems");
const totalValue = document.getElementById("totalValue");

// Elementos para seriales
const componentTypeSelect = document.getElementById("componentType");
const serialProductSelect = document.getElementById("serialProduct");
const serialCode = document.getElementById("serialCode");
const serialForm = document.getElementById("serialForm");
const serialMessage = document.getElementById("serialMessage");

// Elementos para productos
const addProductBtn = document.getElementById("addProductBtn");
const productModal = document.getElementById("productModal");
const closeProductModal = document.getElementById("closeProductModal");
const productForm = document.getElementById("productForm");
const productMessage = document.getElementById("productMessage");
const productTypeSelect = document.getElementById("productTypeSelect");
const productNameInput = document.getElementById("productName");
const productSKUInput = document.getElementById("productSKU");
const productDescription = document.getElementById("productDescription");
const productMarcaInput = document.getElementById("productMarca");
const productModeloInput = document.getElementById("productModelo");

// Elementos para crear nueva categoría
const categoryModal = document.getElementById("categoryModal");
const closeCategoryModal = document.getElementById("closeCategoryModal");
const categoryForm = document.getElementById("categoryForm");
const categoryMessage = document.getElementById("categoryMessage");
const categoryNameInput = document.getElementById("categoryName");
const newCategoryBtn = document.getElementById("newCategoryBtn");

// Elementos comunes
const addItemBtn = document.getElementById("addItemBtn");
const itemModal = document.getElementById("itemModal");
const closeModal = document.getElementById("closeModal");
const searchInput = document.getElementById("searchInput");
const searchBtn = document.getElementById("searchBtn");

// NUEVOS ELEMENTOS PARA LA INTERFAZ MEJORADA
const addMultipleBtn = document.getElementById("addMultipleBtn");

// Elementos para gestión de seriales
const serialsDetailModal = document.getElementById("serialsDetailModal");
const closeSerialsModal = document.getElementById("closeSerialsModal");
const serialsModalTitle = document.getElementById("serialsModalTitle");
const serialsTableBody = document.getElementById("serialsTableBody");

// Nuevos elementos para cambio de estado
const changeStatusModal = document.getElementById("changeStatusModal");
const closeStatusModal = document.getElementById("closeStatusModal");
const newStatusSelect = document.getElementById("newStatus");
const statusNotes = document.getElementById("statusNotes");
const confirmStatusChange = document.getElementById("confirmStatusChange");
const cancelStatusChange = document.getElementById("cancelStatusChange");
const statusMessage = document.getElementById("statusMessage");

// Botones de acciones rápidas
const btnMarcarInstalados = document.getElementById("btnMarcarInstalados");
const btnMarcarDanados = document.getElementById("btnMarcarDanados");
const btnMarcarRetirados = document.getElementById("btnMarcarRetirados");

// ====================================================================
// VARIABLES GLOBALES OPTIMIZADAS
// ====================================================================
let currentUser = null;
let ALL_PRODUCT_MODELS = [];
let productTypesCache = null;
let inventoryCache = null;
let sessionCheckerInterval = null;
let currentProductId = null;
let currentSerialId = null;
let selectedSerials = new Set();

// ====================================================================
// VERIFICACIÓN DE ELEMENTOS DOM
// ====================================================================
function verificarElementosDOM() {
    console.log("🔍 Verificando elementos DOM críticos:");
    
    const elementos = {
        loginScreen,
        dashboardScreen,
        loginForm,
        productTypeSelect,
        productModal,
        categoryModal,
        addProductBtn,
        newCategoryBtn,
        itemModal,
        serialsDetailModal,
        inventoryTableBody
    };
    
    let todosExisten = true;
    for (const [nombre, elemento] of Object.entries(elementos)) {
        if (!elemento) {
            console.error(`❌ Elemento no encontrado: ${nombre}`);
            todosExisten = false;
        } else {
            console.log(`✅ ${nombre}: OK`);
        }
    }
    
    return todosExisten;
}

// ====================================================================
// ANIMACIONES DEL LOGO
// ====================================================================
function initializeAnimations() {
    if (loginScreen && loginScreen.classList.contains('active')) {
        runLoginAnimation();
    }
}

function runLoginAnimation() {
    if (typeof anime === 'undefined') {
        console.warn("⚠️ Anime.js no cargado, omitiendo animaciones");
        return;
    }
    
    const timeline = anime.timeline({
        duration: 1200,
        easing: 'easeOutElastic(1, .8)'
    });

    timeline
    .add({
        targets: '#animatedLogo',
        scale: [0, 1.2, 1],
        rotate: '360deg',
        opacity: [0, 1],
        duration: 1500,
    })
    .add({
        targets: '.company-name',
        opacity: [0, 1],
        translateY: [20, 0],
        duration: 800,
    }, '-=500')
    .add({
        targets: '.login-form',
        opacity: [0, 1],
        translateY: [30, 0],
        duration: 600,
    }, '-=300');
}

function animateDashboardLogo() {
    const dashboardLogo = document.getElementById('dashboardLogo');
    if (dashboardLogo && typeof anime !== 'undefined') {
        anime({
            targets: '#dashboardLogo',
            scale: [0.8, 1],
            rotate: '5deg',
            duration: 800,
            easing: 'easeOutBack'
        });
    }
}

// ====================================================================
// FUNCIONES DE TRANSICIÓN MEJORADAS
// ====================================================================
function showDashboardWithAnimation() {
    if (!loginScreen || !dashboardScreen) return;
    
    dashboardScreen.style.opacity = '0';
    dashboardScreen.style.display = 'flex';
    
    if (typeof anime === 'undefined') {
        // Fallback sin animaciones
        loginScreen.style.display = 'none';
        loginScreen.classList.remove('active');
        dashboardScreen.style.display = 'flex';
        dashboardScreen.classList.add('active');
        dashboardScreen.style.opacity = '1';
        return;
    }
    
    const timeline = anime.timeline({
        duration: 800,
        easing: 'easeInOutQuad',
        complete: function() {
            loginScreen.style.display = 'none';
            dashboardScreen.style.opacity = '';
        }
    });

    timeline
    .add({
        targets: loginScreen,
        opacity: [1, 0],
        translateY: [0, -20],
        duration: 400,
        complete: function() {
            loginScreen.classList.remove('active');
        }
    })
    .add({
        targets: dashboardScreen,
        opacity: [0, 1],
        translateY: [20, 0],
        duration: 500,
        begin: function() {
            dashboardScreen.classList.add('active');
        }
    });
}

function showLoginWithAnimation() {
    if (!loginScreen || !dashboardScreen) return;
    
    loginScreen.style.opacity = '0';
    loginScreen.style.display = 'flex';
    
    if (typeof anime === 'undefined') {
        dashboardScreen.style.display = 'none';
        dashboardScreen.classList.remove('active');
        loginScreen.style.display = 'flex';
        loginScreen.classList.add('active');
        loginScreen.style.opacity = '1';
        return;
    }
    
    const timeline = anime.timeline({
        duration: 800,
        easing: 'easeInOutQuad',
        complete: function() {
            dashboardScreen.style.display = 'none';
            loginScreen.style.opacity = '';
        }
    });

    timeline
    .add({
        targets: dashboardScreen,
        opacity: [1, 0],
        translateY: [0, 20],
        duration: 400,
        complete: function() {
            dashboardScreen.classList.remove('active');
        }
    })
    .add({
        targets: loginScreen,
        opacity: [0, 1],
        translateY: [-20, 0],
        duration: 500,
        begin: function() {
            loginScreen.classList.add('active');
        }
    });
}

// ====================================================================
// MANEJO SEGURO DE AUTENTICACIÓN
// ====================================================================
async function secureLogin(username, password) {
    try {
        const loginBtn = loginForm.querySelector('button[type="submit"]');
        const originalText = loginBtn.innerHTML;
        loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> VERIFICANDO...';
        loginBtn.disabled = true;
        
        console.log(`🔐 Intentando login para: ${username}`);
        
        const response = await fetch(`${AUTH_URL}/login`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ username, password }),
            credentials: 'include'
        });

        console.log(`📨 Respuesta login: ${response.status}`);
        const result = await response.json();

        if (response.ok) {
            currentUser = result.user;
            userInitial.textContent = currentUser.name.charAt(0);
            userName.textContent = currentUser.name;

            showDashboardWithAnimation();
            loginError.style.display = "none";

            setTimeout(() => {
                loadProductosDetallados();
                loadComponentTypes();
                animateDashboardLogo();
            }, 600);
            
            startSessionChecker();
            console.log("✅ Login exitoso");
        } else {
            console.error(`❌ Error login: ${result.error}`);
            showLoginError(result.error || "Credenciales incorrectas");
        }
    } catch (error) {
        console.error("❌ Error de conexión en login:", error);
        showLoginError("Error de conexión con el servidor");
    } finally {
        const loginBtn = loginForm.querySelector('button[type="submit"]');
        if (loginBtn) {
            loginBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> INICIAR SESIÓN';
            loginBtn.disabled = false;
        }
    }
}

async function checkSession() {
    try {
        console.log("🔍 Verificando sesión activa...");
        const response = await fetch(`${AUTH_URL}/check`, {
            credentials: 'include'
        });
        
        console.log(`📨 Respuesta check: ${response.status}`);
        const data = await response.json();
        
        if (response.ok && data.authenticated) {
            currentUser = data.user;
            console.log("✅ Sesión activa encontrada");
            return true;
        } else {
            console.log("❌ No hay sesión activa:", data);
            return false;
        }
    } catch (error) {
        console.error("❌ Error verificando sesión:", error);
        return false;
    }
}

async function secureLogout() {
    try {
        await fetch(`${AUTH_URL}/logout`, {
            method: "POST",
            credentials: 'include'
        });
    } catch (error) {
        console.error("Error en logout:", error);
    } finally {
        currentUser = null;
        ALL_PRODUCT_MODELS = [];
        productTypesCache = null;
        inventoryCache = null;
        selectedSerials.clear();
        
        showLoginWithAnimation();
        
        if (loginForm) loginForm.reset();
        if (loginError) loginError.style.display = "none";
        
        setTimeout(() => {
            runLoginAnimation();
        }, 600);
        
        stopSessionChecker();
        console.log("✅ Logout completado");
    }
}

function startSessionChecker() {
    sessionCheckerInterval = setInterval(async () => {
        const isValid = await checkSession();
        if (!isValid && currentUser) {
            alert("Sesión expirada. Por favor ingresa nuevamente.");
            secureLogout();
        }
    }, 5 * 60 * 1000);
}

function stopSessionChecker() {
    if (sessionCheckerInterval) {
        clearInterval(sessionCheckerInterval);
        sessionCheckerInterval = null;
    }
}

async function secureFetch(url, options = {}) {
    const config = {
        ...options,
        credentials: 'include'
    };
    
    console.log(`📤 Fetch a: ${url}`);
    const response = await fetch(url, config);
    
    if (response.status === 401) {
        console.warn("🔒 Sesión expirada, haciendo logout...");
        secureLogout();
        throw new Error("Sesión expirada");
    }
    
    return response;
}

// ====================================================================
// AUTENTICACIÓN - EVENT LISTENERS
// ====================================================================
if (loginForm) {
    loginForm.addEventListener("submit", (e) => {
        e.preventDefault();
        const username = document.getElementById("username").value;
        const password = document.getElementById("password").value;
        
        secureLogin(username, password);
    });
}

if (logoutBtn) {
    logoutBtn.addEventListener("click", secureLogout);
}

function showLoginError(message) {
    if (!loginError) return;
    
    loginError.textContent = message;
    loginError.style.display = "block";
    
    if (typeof anime !== 'undefined') {
        anime({
            targets: '#loginError',
            scale: [0.8, 1],
            duration: 300,
            easing: 'easeOutBack'
        });
    }
}

// ====================================================================
// GESTIÓN DE INVENTARIO - NUEVA VERSIÓN MEJORADA
// ====================================================================
async function loadProductosDetallados(filter = "") {
    try {
        console.log(`📊 Cargando productos detallados (filtro: "${filter}")`);
        
        if (inventoryTableBody) {
            inventoryTableBody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px;">
                        <div class="loading"></div> Cargando inventario...
                    </td>
                </tr>
            `;
        }

        const [productosResponse, statsResponse] = await Promise.all([
            secureFetch(`${INVENTARIO_URL}/productos/detallado`),
            secureFetch(`${INVENTARIO_URL}/estadisticas`)
        ]);

        if (!productosResponse.ok) {
            throw new Error(`Error HTTP: ${productosResponse.status}`);
        }

        let productos = await productosResponse.json();
        inventoryCache = productos;
        renderProductosTabla(productos, filter);

        if (statsResponse.ok) {
            const stats = await statsResponse.json();
            updateStatistics(
                stats.total_modelos || productos.length,
                stats.modelos_stock_bajo || 0,
                stats.total_seriales || 0
            );
        } else {
            updateStatisticsFromData(productos);
        }

    } catch (error) {
        console.error("❌ Error al cargar productos detallados:", error);
        if (error.message === "Sesión expirada") {
            showLoginError("Sesión expirada");
        } else {
            showInventoryError();
        }
    }
}

function updateStatisticsFromData(productos) {
    let totalSeriales = 0;
    let lowStockCount = 0;

    productos.forEach(producto => {
        totalSeriales += producto.total || 0;
        if ((producto.almacen || 0) <= 3) lowStockCount++;
    });

    updateStatistics(productos.length, lowStockCount, totalSeriales);
}

function renderProductosTabla(productos, filter = "") {
    if (!inventoryTableBody) return;
    
    if (filter) {
        const lowerFilter = filter.toLowerCase();
        productos = productos.filter(p =>
            (p.marca && p.marca.toLowerCase().includes(lowerFilter)) ||
            (p.modelo && p.modelo.toLowerCase().includes(lowerFilter)) ||
            (p.nombre && p.nombre.toLowerCase().includes(lowerFilter)) ||
            (p.codigo_sku && p.codigo_sku.toLowerCase().includes(lowerFilter)) ||
            (p.categoria && p.categoria.toLowerCase().includes(lowerFilter))
        );
    }

    inventoryTableBody.innerHTML = "";
    let totalProductos = 0;
    let totalSeriales = 0;
    let lowStockCount = 0;

    if (productos.length === 0) {
        inventoryTableBody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px;">
                    <i class="fas fa-search" style="font-size: 24px; margin-bottom: 10px; display: block; color: var(--color-text-secondary);"></i>
                    No se encontraron productos que coincidan con la búsqueda.
                </td>
            </tr>
        `;
        updateStatistics(0, 0, 0);
        return;
    }

    productos.forEach((producto, index) => {
        const row = document.createElement("tr");
        const almacen = producto.almacen || 0;
        const total = producto.total || 0;
        
        // Determinar nivel de stock
        let stockClass = "stock-normal";
        let stockIcon = "✓";
        
        if (almacen === 0) {
            stockClass = "stock-agotado";
            stockIcon = "✗";
        } else if (almacen <= 3) {
            stockClass = "stock-bajo";
            stockIcon = "!";
        } else if (almacen <= 10) {
            stockClass = "stock-medio";
            stockIcon = "~";
        }
        
        totalProductos++;
        totalSeriales += total;
        if (almacen <= 3) lowStockCount++;

        row.innerHTML = `
            <td>
                <strong>${producto.marca || 'Sin marca'}</strong>
            </td>
            <td>
                <div class="producto-info">
                    <div class="producto-modelo">${producto.modelo || producto.nombre}</div>
                    ${producto.categoria ? `<div class="producto-categoria">${producto.categoria}</div>` : ''}
                </div>
            </td>
            <td>
                <div class="sku-display">
                    <code>${producto.codigo_sku}</code>
                </div>
            </td>
            <td>
                <div class="stock-display ${stockClass}" 
                     onclick="mostrarSerialesDetalle(${producto.producto_id}, '${(producto.modelo || producto.nombre).replace(/'/g, "\\'")}')">
                    <span class="stock-icon">${stockIcon}</span>
                    <span class="stock-number">${total}</span>
                    <span class="stock-label">unidades</span>
                    <i class="fas fa-eye stock-eye"></i>
                </div>
                <div class="stock-detalle">
                    <small>${almacen} en almacén</small>
                </div>
            </td>
            <td>
                <div class="distribucion-estados">
                    ${producto.almacen > 0 ? `
                        <span class="estado-badge estado-almacen" title="En almacén">
                            <i class="fas fa-warehouse"></i> ${producto.almacen}
                        </span>
                    ` : ''}
                    ${producto.instalado > 0 ? `
                        <span class="estado-badge estado-instalado" title="Instalados">
                            <i class="fas fa-wrench"></i> ${producto.instalado}
                        </span>
                    ` : ''}
                    ${producto.danado > 0 ? `
                        <span class="estado-badge estado-danado" title="Dañados">
                            <i class="fas fa-times-circle"></i> ${producto.danado}
                        </span>
                    ` : ''}
                    ${producto.retirado > 0 ? `
                        <span class="estado-badge estado-retirado" title="Retirados">
                            <i class="fas fa-truck"></i> ${producto.retirado}
                        </span>
                    ` : ''}
                </div>
            </td>
            <td>
                <div class="acciones-rapidas">
                    <button class="btn-accion btn-agregar" 
                            onclick="agregarStockMultiple(${producto.producto_id}, '${(producto.modelo || producto.nombre).replace(/'/g, "\\'")}')"
                            title="Agregar más unidades">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button class="btn-accion btn-editar"
                            onclick="editarProducto(${producto.producto_id})"
                            title="Editar producto">
                        <i class="fas fa-edit"></i>
                    </button>
                    ${currentUser?.role === 'admin' ? `
                    <button class="btn-accion btn-eliminar"
                            onclick="eliminarProducto(${producto.producto_id}, '${(producto.modelo || producto.nombre).replace(/'/g, "\\'")}')"
                            title="Eliminar producto">
                        <i class="fas fa-trash"></i>
                    </button>
                    ` : ''}
                </div>
            </td>
        `;

        inventoryTableBody.appendChild(row);
        
        if (typeof anime !== 'undefined') {
            anime({
                targets: row,
                opacity: [0, 1],
                translateY: [20, 0],
                duration: 400,
                delay: index * 50,
                easing: 'easeOutQuad'
            });
        }
    });

    updateStatistics(totalProductos, lowStockCount, totalSeriales);
}

function showInventoryError() {
    if (!inventoryTableBody) return;
    
    inventoryTableBody.innerHTML = `
        <tr>
            <td colspan="6" style="text-align: center; color: var(--color-danger); padding: 40px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                Error al conectar con el servidor. Verifica tu conexión.
            </td>
        </tr>
    `;
}

// ====================================================================
// FUNCIONALIDADES NUEVAS
// ====================================================================

// 1. AGREGAR STOCK MULTIPLE
function agregarStockMultiple(productoId, productoNombre) {
    // Crear modal dinámico
    const modalHTML = `
        <div class="modal" id="addStockModal" style="display: flex;">
            <div class="modal-content" style="max-width: 500px;">
                <div class="modal-header">
                    <h2><i class="fas fa-layer-group"></i> Agregar Stock</h2>
                    <button class="close-btn" onclick="cerrarModal('addStockModal')">&times;</button>
                </div>
                <div style="padding: 25px;">
                    <div class="alert alert-info" style="margin-bottom: 20px;">
                        <i class="fas fa-info-circle"></i> 
                        Agregar múltiples unidades de: <strong>${productoNombre}</strong>
                    </div>
                    
                    <div class="form-group">
                        <label for="cantidadUnidades">
                            <i class="fas fa-boxes"></i> Cantidad de unidades
                        </label>
                        <input type="number" id="cantidadUnidades" class="form-control" 
                               min="1" max="100" value="1" required>
                        <small style="color: var(--color-text-secondary); font-size: 12px; margin-top: 5px;">
                            Número de unidades físicas a agregar (máx. 100)
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="estadoInicial">
                            <i class="fas fa-info-circle"></i> Estado inicial
                        </label>
                        <select id="estadoInicial" class="form-control">
                            <option value="ALMACEN">🟢 Almacén (Disponible)</option>
                            <option value="INSTALADO">🔵 Instalado (En uso)</option>
                            <option value="DAÑADO">🔴 Dañado (No usable)</option>
                            <option value="RETIRADO">🟡 Retirado (Fuera de servicio)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="prefijoSerial">
                            <i class="fas fa-tag"></i> Prefijo opcional para seriales
                        </label>
                        <input type="text" id="prefijoSerial" class="form-control" 
                               placeholder="Ej: LOTE1, COMPRA2024, INVENTARIO...">
                        <small style="color: var(--color-text-secondary); font-size: 12px; margin-top: 5px;">
                            Opcional: añade un prefijo a los seriales generados
                        </small>
                    </div>
                    
                    <div style="display: flex; gap: 10px; margin-top: 25px;">
                        <button type="button" class="btn btn-success" style="flex: 1;" onclick="procesarAgregarStock(${productoId})">
                            <i class="fas fa-plus-circle"></i> Agregar Stock
                        </button>
                        <button type="button" class="btn btn-danger" onclick="cerrarModal('addStockModal')">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    </div>
                    
                    <div id="addStockMessage" class="alert" style="display: none; margin-top: 20px;"></div>
                </div>
            </div>
        </div>
    `;
    
    // Agregar modal al DOM
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Animación
    if (typeof anime !== 'undefined') {
        const modal = document.getElementById('addStockModal');
        anime({
            targets: modal.querySelector('.modal-content'),
            opacity: [0, 1],
            scale: [0.9, 1],
            duration: 400,
            easing: 'easeOutBack'
        });
    }
}

async function procesarAgregarStock(productoId) {
    const cantidad = document.getElementById('cantidadUnidades').value;
    const estado = document.getElementById('estadoInicial').value;
    const prefijo = document.getElementById('prefijoSerial').value.trim();
    const messageDiv = document.getElementById('addStockMessage');
    
    if (!cantidad || cantidad < 1) {
        showMessage(messageDiv, "❌ Ingresa una cantidad válida", "danger");
        return;
    }
    
    try {
        const response = await secureFetch(`${INVENTARIO_URL}/agregar_lote`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                producto_id: productoId,
                cantidad: parseInt(cantidad),
                estado: estado,
                prefijo: prefijo || null
            })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            showMessage(messageDiv, `✅ ${result.mensaje}`, "success");
            
            // Actualizar tabla después de 1.5 segundos
            setTimeout(() => {
                loadProductosDetallados();
                cerrarModal('addStockModal');
            }, 1500);
        } else {
            showMessage(messageDiv, `❌ ${result.error}`, "danger");
        }
    } catch (error) {
        console.error("Error agregando stock:", error);
        showMessage(messageDiv, "❌ Error de conexión con el servidor", "danger");
    }
}

// 2. VER SERIALES DETALLADOS
async function mostrarSerialesDetalle(productoId, productoNombre) {
    currentProductId = productoId;
    
    // Actualizar título del modal existente
    if (serialsModalTitle) {
        serialsModalTitle.innerHTML = `<i class="fas fa-boxes"></i> Seriales: ${productoNombre}`;
    }
    
    // Mostrar modal existente
    if (serialsDetailModal) {
        serialsDetailModal.style.display = "flex";
        
        if (typeof anime !== 'undefined') {
            anime({
                targets: serialsDetailModal.querySelector('.modal-content'),
                opacity: [0, 1],
                scale: [0.9, 1],
                duration: 400,
                easing: 'easeOutBack'
            });
        }
    }
    
    // Cargar seriales
    try {
        const response = await secureFetch(`${INVENTARIO_URL}/seriales/${productoId}`);
        if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);

        const seriales = await response.json();
        renderSerialsTable(seriales, productoNombre);
    } catch (error) {
        console.error("❌ Error al cargar seriales:", error);
        if (serialsTableBody) {
            serialsTableBody.innerHTML = `
                <tr>
                    <td colspan="5" style="text-align: center; color: var(--color-danger); padding: 20px;">
                        <i class="fas fa-exclamation-triangle"></i> Error al obtener seriales
                    </td>
                </tr>
            `;
        }
    }
}

function cerrarModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    if (typeof anime !== 'undefined') {
        anime({
            targets: modal.querySelector('.modal-content'),
            opacity: [1, 0],
            scale: [1, 0.9],
            duration: 300,
            easing: 'easeInQuad',
            complete: function() {
                modal.remove();
            }
        });
    } else {
        modal.remove();
    }
}

// ====================================================================
// GESTIÓN DE SERIALES (existente, mantener)
// ====================================================================
async function loadComponentTypes() {
    try {
        console.log("📦 Cargando tipos de pieza...");
        
        if (!componentTypeSelect) {
            console.error("❌ componentTypeSelect no encontrado");
            return;
        }

        if (productTypesCache) {
            populateComponentTypes(productTypesCache);
            return;
        }

        const response = await secureFetch(`${INVENTARIO_URL}/tipos_pieza`);
        if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);

        const types = await response.json();
        console.log(`✅ Tipos cargados: ${types.length}`);
        
        if (types.length === 0) {
            await inicializarTiposPredeterminados();
            const newResponse = await secureFetch(`${INVENTARIO_URL}/tipos_pieza`);
            const newTypes = await newResponse.json();
            productTypesCache = newTypes;
            populateComponentTypes(newTypes);
        } else {
            productTypesCache = types;
            populateComponentTypes(types);
        }
        
        await fetchAllProductModels();

    } catch (error) {
        console.error("❌ Error al cargar tipos de pieza:", error);
        if (componentTypeSelect) {
            componentTypeSelect.innerHTML = '<option value="">Error al cargar categorías</option>';
        }
    }
}

async function inicializarTiposPredeterminados() {
    try {
        console.log("📦 Inicializando tipos predeterminados...");
        const response = await secureFetch(`${INVENTARIO_URL}/inicializar_tipos`, {
            method: 'POST'
        });
        
        if (response.ok) {
            const result = await response.json();
            console.log("✅ Tipos predeterminados inicializados:", result.mensaje);
        }
    } catch (error) {
        console.error("Error inicializando tipos predeterminados:", error);
    }
}

function populateComponentTypes(types) {
    if (!componentTypeSelect) return;
    
    componentTypeSelect.innerHTML = '<option value="">-- Selecciona una categoría --</option>';
    types.forEach((t) => {
        const option = document.createElement("option");
        option.value = t.tipo_id;
        option.textContent = t.tipo_modelo;
        componentTypeSelect.appendChild(option);
    });
}

async function fetchAllProductModels() {
    try {
        console.log("📦 Cargando todos los modelos...");
        const response = await secureFetch(`${INVENTARIO_URL}/productos`);
        if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
        
        ALL_PRODUCT_MODELS = await response.json();
        console.log(`✅ Modelos cargados: ${ALL_PRODUCT_MODELS.length}`);
    } catch (error) {
        console.error("❌ Error al cargar todos los modelos:", error);
        if (serialProductSelect) {
            serialProductSelect.innerHTML = '<option value="">Error al cargar modelos</option>';
        }
    }
}

function filterProductModels() {
    if (!serialProductSelect) return;
    
    const selectedTypeId = parseInt(componentTypeSelect.value);
    
    if (!selectedTypeId) {
        serialProductSelect.innerHTML = '<option value="">-- Selecciona un modelo (elige categoría primero) --</option>';
        serialProductSelect.disabled = true;
        return;
    }

    serialProductSelect.innerHTML = '<option value="">-- Selecciona un modelo específico --</option>';
    const filteredModels = ALL_PRODUCT_MODELS.filter(model => model.tipo_pieza_id === selectedTypeId);

    if (filteredModels.length === 0) {
        serialProductSelect.innerHTML = '<option value="">-- No hay modelos para esta categoría --</option>';
    } else {
        filteredModels.forEach(model => {
            const option = document.createElement('option');
            option.value = model.producto_id;
            option.textContent = `${model.nombre} (SKU: ${model.codigo_sku})`;
            serialProductSelect.appendChild(option);
        });
    }

    serialProductSelect.disabled = false;
}

function renderSerialsTable(serials, productoNombre) {
    if (!serialsTableBody) return;
    
    serialsTableBody.innerHTML = "";

    if (serials.length === 0) {
        serialsTableBody.innerHTML = `
            <tr>
                <td colspan="5" style="text-align: center; padding: 40px; color: var(--color-text-secondary);">
                    <i class="fas fa-inbox" style="font-size: 32px; margin-bottom: 10px; display: block;"></i>
                    No hay seriales registrados para <strong>${productoNombre}</strong>
                </td>
            </tr>
        `;
        return;
    }

    serials.forEach((s, index) => {
        const row = document.createElement("tr");
        row.className = `serial-row ${s.estado.toLowerCase()}`;
        
        const statusClass = s.estado === 'ALMACEN' ? 'success' : 
                           s.estado === 'INSTALADO' ? 'primary' : 
                           s.estado === 'DAÑADO' ? 'danger' : 'warning';

        row.innerHTML = `
            <td>${s.serial_id}</td>
            <td><code>${s.codigo_unico_serial}</code></td>
            <td>
                <span class="badge badge-${statusClass}">
                    ${s.estado === 'ALMACEN' ? '🟢' : s.estado === 'INSTALADO' ? '🔵' : s.estado === 'DAÑADO' ? '🔴' : '🟡'} ${s.estado}
                </span>
            </td>
            <td>${s.fecha_ingreso || 'N/A'}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn-action change-status" data-serial-id="${s.serial_id}" data-current-status="${s.estado}" title="Cambiar estado">
                        <i class="fas fa-exchange-alt"></i>
                    </button>
                    ${currentUser?.role === 'admin' ? `
                    <button class="btn-action delete-serial" data-serial-id="${s.serial_id}" data-serial-code="${s.codigo_unico_serial}" title="Eliminar serial">
                        <i class="fas fa-trash"></i>
                    </button>
                    ` : ''}
                </div>
            </td>
        `;

        serialsTableBody.appendChild(row);
        
        if (typeof anime !== 'undefined') {
            anime({
                targets: row,
                opacity: [0, 1],
                translateX: [-20, 0],
                duration: 300,
                delay: index * 80,
                easing: 'easeOutQuad'
            });
        }
    });
    
    attachSerialActionEvents();
}

// ====================================================================
// EVENTOS PARA SERIALES - NUEVA FUNCIÓN (MODIFICADA)
// ====================================================================
function attachSerialActionEvents() {
    console.log("🔧 Attaching serial action events...");
    
    // Usar event delegation para manejar botones dinámicos
    if (serialsTableBody) {
        serialsTableBody.addEventListener('click', handleSerialActions);
    }
}

function handleSerialActions(event) {
    const target = event.target;
    
    // Verificar si se hizo click en un botón de cambio de estado
    const changeStatusBtn = target.closest('.change-status');
    if (changeStatusBtn) {
        const serialId = changeStatusBtn.dataset.serialId;
        const estadoActual = changeStatusBtn.dataset.currentStatus;
        cambiarEstadoSerial(serialId, estadoActual);
        return;
    }
    
    // Verificar si se hizo click en un botón de eliminar
    const deleteSerialBtn = target.closest('.delete-serial');
    if (deleteSerialBtn) {
        const serialId = deleteSerialBtn.dataset.serialId;
        const codigoSerial = deleteSerialBtn.dataset.serialCode;
        eliminarSerial(serialId, codigoSerial);
        return;
    }
}

async function cambiarEstadoSerial(serialId, estadoActual) {
    // Crear modal para seleccionar nuevo estado
    const modalHTML = `
        <div class="modal" id="cambiarEstadoModal" style="display: flex;">
            <div class="modal-content" style="max-width: 500px;">
                <div class="modal-header">
                    <h2><i class="fas fa-exchange-alt"></i> Cambiar Estado del Serial</h2>
                    <button class="close-btn" onclick="cerrarModal('cambiarEstadoModal')">&times;</button>
                </div>
                <div style="padding: 25px;">
                    <div class="alert alert-info" style="margin-bottom: 20px;">
                        <i class="fas fa-info-circle"></i> 
                        Estado actual: <strong>${estadoActual}</strong>
                    </div>
                    
                    <div class="form-group">
                        <label for="nuevoEstadoSelect">
                            <i class="fas fa-info-circle"></i> Nuevo estado
                        </label>
                        <select id="nuevoEstadoSelect" class="form-control">
                            <option value="ALMACEN" ${estadoActual === 'ALMACEN' ? 'selected' : ''}>🟢 Almacén (Disponible)</option>
                            <option value="INSTALADO" ${estadoActual === 'INSTALADO' ? 'selected' : ''}>🔵 Instalado (En uso)</option>
                            <option value="DAÑADO" ${estadoActual === 'DAÑADO' ? 'selected' : ''}>🔴 Dañado (No usable)</option>
                            <option value="RETIRADO" ${estadoActual === 'RETIRADO' ? 'selected' : ''}>🟡 Retirado (Fuera de servicio)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="notasCambioEstado">
                            <i class="fas fa-sticky-note"></i> Notas (opcional)
                        </label>
                        <textarea id="notasCambioEstado" class="form-control" 
                                  placeholder="Motivo del cambio de estado..." rows="3"></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 10px; margin-top: 25px;">
                        <button type="button" class="btn btn-primary" style="flex: 1;" onclick="confirmarCambioEstado(${serialId})">
                            <i class="fas fa-check-circle"></i> Confirmar Cambio
                        </button>
                        <button type="button" class="btn btn-danger" onclick="cerrarModal('cambiarEstadoModal')">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    </div>
                    
                    <div id="cambiarEstadoMessage" class="alert" style="display: none; margin-top: 20px;"></div>
                </div>
            </div>
        </div>
    `;
    
    // Agregar modal al DOM
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Animación
    if (typeof anime !== 'undefined') {
        const modal = document.getElementById('cambiarEstadoModal');
        anime({
            targets: modal.querySelector('.modal-content'),
            opacity: [0, 1],
            scale: [0.9, 1],
            duration: 400,
            easing: 'easeOutBack'
        });
    }
}

async function confirmarCambioEstado(serialId) {
    const nuevoEstado = document.getElementById('nuevoEstadoSelect').value;
    const notas = document.getElementById('notasCambioEstado').value.trim();
    const messageDiv = document.getElementById('cambiarEstadoMessage');
    
    if (!nuevoEstado) {
        showMessage(messageDiv, "❌ Por favor selecciona un estado", "danger");
        return;
    }
    
    try {
        const response = await secureFetch(`${INVENTARIO_URL}/serial/${serialId}`, {
            method: 'PUT',
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ 
                estado: nuevoEstado,
                notas: notas || `Cambio de estado realizado por ${currentUser?.name || 'usuario'}`
            })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            showMessage(messageDiv, `✅ ${result.mensaje || 'Estado cambiado correctamente'}`, "success");
            
            // Cerrar modal y actualizar después de 1.5 segundos
            setTimeout(() => {
                cerrarModal('cambiarEstadoModal');
                
                // Recargar los seriales si estamos en el modal de detalle
                if (currentProductId) {
                    mostrarSerialesDetalle(currentProductId, serialsModalTitle?.textContent.replace('Seriales: ', '') || '');
                }
                
                // Actualizar la vista general
                loadProductosDetallados();
            }, 1500);
        } else {
            showMessage(messageDiv, `❌ ${result.error || 'No se pudo cambiar el estado'}`, "danger");
        }
    } catch (error) {
        console.error('Error cambiando estado:', error);
        showMessage(messageDiv, "❌ Error de conexión con el servidor", "danger");
    }
}

async function eliminarSerial(serialId, codigoSerial) {
    if (!confirm(`¿Estás seguro de eliminar el serial ${codigoSerial}?\n\nEsta acción no se puede deshacer y eliminará permanentemente este registro.`)) {
        return;
    }
    
    try {
        const response = await secureFetch(`${INVENTARIO_URL}/serial/${serialId}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (response.ok) {
            alert(`✅ ${result.mensaje || 'Serial eliminado correctamente'}`);
            
            // Recargar los seriales si estamos en el modal de detalle
            if (currentProductId) {
                mostrarSerialesDetalle(currentProductId, serialsModalTitle?.textContent.replace('Seriales: ', '') || '');
            }
            
            // Actualizar la vista general
            loadProductosDetallados();
        } else {
            alert(`❌ ${result.error || 'No se pudo eliminar el serial'}`);
        }
    } catch (error) {
        console.error('Error eliminando serial:', error);
        alert('❌ Error de conexión con el servidor');
    }
}

// ====================================================================
// REGISTRO DE SERIALES (existente, mantener)
// ====================================================================
if (serialForm) {
    serialForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        if (serialMessage) serialMessage.style.display = "none";

        if (!componentTypeSelect || !serialProductSelect) {
            showMessage(serialMessage, "❌ Error en formulario", "danger");
            return;
        }

        if (!componentTypeSelect.value || !serialProductSelect.value) {
            showMessage(serialMessage, "❌ Por favor selecciona tanto la categoría como el modelo", "danger");
            return;
        }

        const newSerial = {
            producto_id: Number.parseInt(serialProductSelect.value),
            codigo_unico_serial: serialCode ? serialCode.value.trim() : "",
        };

        if (!newSerial.codigo_unico_serial) {
            showMessage(serialMessage, "❌ Por favor ingresa un código de serial", "danger");
            return;
        }

        try {
            const response = await secureFetch(`${INVENTARIO_URL}/serial`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(newSerial),
            });

            const result = await response.json();

            if (response.ok) {
                showMessage(serialMessage, `✅ ${result.mensaje}`, "success");
                serialForm.reset();
                inventoryCache = null;
                loadProductosDetallados();
                
                if (typeof anime !== 'undefined') {
                    anime({
                        targets: serialMessage,
                        scale: [0.8, 1],
                        duration: 300,
                        easing: 'easeOutBack'
                    });
                }
                
                setTimeout(() => {
                    if (itemModal) itemModal.style.display = "none";
                    if (serialMessage) serialMessage.style.display = "none";
                }, 2000);
            } else {
                showMessage(serialMessage, `❌ ${result.error}`, "danger");
            }
        } catch (error) {
            console.error("Error al registrar serial:", error);
            showMessage(serialMessage, "❌ Error de conexión con el servidor", "danger");
        }
    });
}

// ====================================================================
// GESTIÓN DE PRODUCTOS (existente, mantener)
// ====================================================================
if (addProductBtn) {
    addProductBtn.addEventListener("click", () => {
        console.log("🆕 Abriendo modal de producto...");
        if (productModal) {
            productModal.style.display = "flex";
        }
        if (productForm) productForm.reset();
        if (productMessage) productMessage.style.display = "none";
        loadProductTypes();
        
        if (typeof anime !== 'undefined') {
            anime({
                targets: productModal,
                opacity: [0, 1],
                scale: [0.9, 1],
                duration: 400,
                easing: 'easeOutBack'
            });
        }
    });
}

async function loadProductTypes() {
    try {
        console.log("📦 Cargando categorías para producto...");
        
        if (!productTypeSelect) {
            console.error("❌ productTypeSelect no encontrado");
            return;
        }

        productTypeSelect.innerHTML = '<option value="">Cargando categorías...</option>';
        
        if (productTypesCache) {
            console.log("📦 Usando caché de categorías:", productTypesCache.length);
            populateProductTypes(productTypesCache);
            return;
        }

        const response = await secureFetch(`${INVENTARIO_URL}/tipos_pieza`);
        if (!response.ok) throw new Error('Error al cargar categorías');
        
        const types = await response.json();
        console.log(`✅ Categorías cargadas: ${types.length} tipos`);
        
        if (types.length === 0) {
            console.log("📦 No hay categorías, inicializando predeterminadas...");
            await inicializarTiposPredeterminados();
            const newResponse = await secureFetch(`${INVENTARIO_URL}/tipos_pieza`);
            const newTypes = await newResponse.json();
            productTypesCache = newTypes;
            populateProductTypes(newTypes);
        } else {
            productTypesCache = types;
            populateProductTypes(types);
        }
        
    } catch (error) {
        console.error("❌ Error al cargar categorías:", error);
        if (productTypeSelect) {
            productTypeSelect.innerHTML = `
                <option value="">Error cargando categorías</option>
                <option value="reload" onclick="location.reload()">↻ Recargar página</option>
            `;
        }
    }
}

function populateProductTypes(types) {
    if (!productTypeSelect) return;
    
    productTypeSelect.innerHTML = '<option value="">-- Selecciona una categoría --</option>';
    types.forEach((t) => {
        const option = document.createElement("option");
        option.value = t.tipo_id;
        option.textContent = t.tipo_modelo;
        productTypeSelect.appendChild(option);
    });
    
    console.log(`✅ Select poblado con ${types.length} categorías`);
}

if (productForm) {
    productForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        if (productMessage) productMessage.style.display = "none";

        if (!productNameInput || !productTypeSelect || !productSKUInput || !productMarcaInput || !productModeloInput) {
            showMessage(productMessage, "❌ Error en formulario", "danger");
            return;
        }

        const newProduct = {
            nombre: productNameInput.value.trim(),
            descripcion: productDescription ? productDescription.value.trim() : "",
            tipo_pieza_id: parseInt(productTypeSelect.value),
            codigo_sku: productSKUInput.value.trim(),
            marca: productMarcaInput.value.trim(),
            modelo: productModeloInput.value.trim()
        };

        if (!newProduct.nombre || !newProduct.tipo_pieza_id || !newProduct.codigo_sku || !newProduct.marca || !newProduct.modelo) {
            showMessage(productMessage, "❌ Por favor completa todos los campos requeridos", "danger");
            return;
        }

        try {
            const response = await secureFetch(`${INVENTARIO_URL}/productos`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(newProduct),
            });

            const result = await response.json();

            if (response.ok) {
                showMessage(productMessage, `✅ ${result.mensaje}`, "success");
                productForm.reset();
                
                ALL_PRODUCT_MODELS = [];
                productTypesCache = null;
                inventoryCache = null;
                
                await fetchAllProductModels();
                loadProductosDetallados();
                loadComponentTypes();
                
                if (typeof anime !== 'undefined') {
                    anime({
                        targets: productMessage,
                        scale: [0.8, 1],
                        duration: 300,
                        easing: 'easeOutBack'
                    });
                }
                
                setTimeout(() => {
                    if (productModal) productModal.style.display = "none";
                    if (productMessage) productMessage.style.display = "none";
                }, 2000);
            } else {
                showMessage(productMessage, `❌ ${result.error}`, "danger");
            }
        } catch (error) {
            console.error("Error al registrar producto:", error);
            showMessage(productMessage, "❌ Error de conexión con el servidor", "danger");
        }
    });
}

// ====================================================================
// ELIMINAR PRODUCTO (existente, mantener)
// ====================================================================
async function eliminarProducto(productoId, productoNombre) {
    if (!confirm(`¿Eliminar el producto "${productoNombre}"?\n\nEsta acción eliminará TODOS sus seriales y no se puede deshacer.`)) {
        return;
    }

    try {
        const response = await secureFetch(`${INVENTARIO_URL}/productos/${productoId}`, {
            method: "DELETE"
        });

        const result = await response.json();

        if (response.ok) {
            alert(`✅ ${result.mensaje}`);
            loadProductosDetallados();
        } else {
            alert(`❌ ${result.error}`);
        }
    } catch (error) {
        console.error("Error eliminando producto:", error);
        alert("❌ Error de conexión");
    }
}

// ====================================================================
// EDITAR PRODUCTO - FUNCIÓN COMPLETA
// ====================================================================
async function editarProducto(productoId) {
    try {
        console.log(`✏️ Editando producto ID: ${productoId}`);
        
        // 1. Obtener datos actuales del producto
        const response = await secureFetch(`${INVENTARIO_URL}/productos/${productoId}`);
        if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
        
        const producto = await response.json();
        
        // 2. Obtener categorías disponibles
        const categoriasResponse = await secureFetch(`${INVENTARIO_URL}/tipos_pieza`);
        const categorias = await categoriasResponse.json();
        
        // 3. Crear modal de edición
        const modalHTML = `
            <div class="modal" id="editarProductoModal" style="display: flex;">
                <div class="modal-content" style="max-width: 600px;">
                    <div class="modal-header">
                        <h2><i class="fas fa-edit"></i> Editar Producto</h2>
                        <button class="close-btn" onclick="cerrarModal('editarProductoModal')">&times;</button>
                    </div>
                    <div style="padding: 25px;">
                        <div class="alert alert-info" style="margin-bottom: 20px;">
                            <i class="fas fa-info-circle"></i> 
                            Editando: <strong>${producto.nombre.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</strong>
                        </div>
                        
                        <div class="form-group">
                            <label for="editarNombre">
                                <i class="fas fa-tag"></i> Nombre del Producto
                            </label>
                            <input type="text" id="editarNombre" class="form-control" 
                                   value="${producto.nombre.replace(/"/g, '&quot;').replace(/'/g, "\\'")}"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="editarMarca">
                                <i class="fas fa-tag"></i> Marca
                            </label>
                            <input type="text" id="editarMarca" class="form-control" 
                                   value="${(producto.marca || '').replace(/"/g, '&quot;').replace(/'/g, "\\'")}"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="editarModelo">
                                <i class="fas fa-cube"></i> Modelo
                            </label>
                            <input type="text" id="editarModelo" class="form-control" 
                                   value="${(producto.modelo || '').replace(/"/g, '&quot;').replace(/'/g, "\\'")}"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="editarDescripcion">
                                <i class="fas fa-align-left"></i> Descripción
                            </label>
                            <textarea id="editarDescripcion" class="form-control" rows="3">${producto.descripcion || ''}</textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="editarCategoria">
                                <i class="fas fa-folder"></i> Categoría
                            </label>
                            <select id="editarCategoria" class="form-control" required>
                                <option value="">-- Selecciona categoría --</option>
                                ${categorias.map(cat => `
                                    <option value="${cat.tipo_id}" ${producto.tipo_pieza_id === cat.tipo_id ? 'selected' : ''}>
                                        ${cat.tipo_modelo}
                                    </option>
                                `).join('')}
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="editarSKU">
                                <i class="fas fa-barcode"></i> Código SKU
                            </label>
                            <input type="text" id="editarSKU" class="form-control" 
                                   value="${producto.codigo_sku}"
                                   required>
                            <small style="color: var(--color-text-secondary); font-size: 12px; margin-top: 5px;">
                                Código único. No puede repetirse con otros productos.
                            </small>
                        </div>
                        
                        <div style="display: flex; gap: 10px; margin-top: 25px;">
                            <button type="button" class="btn btn-primary" style="flex: 1;" onclick="guardarCambiosProducto(${productoId})">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                            <button type="button" class="btn btn-danger" onclick="cerrarModal('editarProductoModal')">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                        </div>
                        
                        <div id="editarProductoMessage" class="alert" style="display: none; margin-top: 20px;"></div>
                    </div>
                </div>
            </div>
        `;
        
        // 4. Agregar modal al DOM
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // 5. Animación
        if (typeof anime !== 'undefined') {
            const modal = document.getElementById('editarProductoModal');
            anime({
                targets: modal.querySelector('.modal-content'),
                opacity: [0, 1],
                scale: [0.9, 1],
                duration: 400,
                easing: 'easeOutBack'
            });
        }
        
    } catch (error) {
        console.error('❌ Error al cargar datos para edición:', error);
        alert('Error al cargar datos del producto. Intenta de nuevo.');
    }
}

// ====================================================================
// GUARDAR CAMBIOS DE PRODUCTO
// ====================================================================
async function guardarCambiosProducto(productoId) {
    const nombre = document.getElementById('editarNombre').value.trim();
    const marca = document.getElementById('editarMarca').value.trim();
    const modelo = document.getElementById('editarModelo').value.trim();
    const descripcion = document.getElementById('editarDescripcion').value.trim();
    const categoriaId = document.getElementById('editarCategoria').value;
    const sku = document.getElementById('editarSKU').value.trim();
    const messageDiv = document.getElementById('editarProductoMessage');
    
    // Validación
    if (!nombre || !marca || !modelo || !categoriaId || !sku) {
        showMessage(messageDiv, "❌ Todos los campos marcados son obligatorios", "danger");
        return;
    }
    
    try {
        const response = await secureFetch(`${INVENTARIO_URL}/productos/${productoId}`, {
            method: 'PUT',
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                nombre: nombre,
                marca: marca,
                modelo: modelo,
                descripcion: descripcion,
                tipo_pieza_id: parseInt(categoriaId),
                codigo_sku: sku
            })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            showMessage(messageDiv, `✅ ${result.mensaje || 'Producto actualizado correctamente'}`, "success");
            
            // Actualizar después de 1.5 segundos
            setTimeout(() => {
                cerrarModal('editarProductoModal');
                loadProductosDetallados(); // Recargar tabla
                
                // Actualizar caché si es necesario
                ALL_PRODUCT_MODELS = [];
                inventoryCache = null;
                
                console.log(`✅ Producto ${productoId} actualizado`);
            }, 1500);
        } else {
            showMessage(messageDiv, `❌ ${result.error || 'No se pudo actualizar el producto'}`, "danger");
        }
    } catch (error) {
        console.error('❌ Error guardando cambios:', error);
        showMessage(messageDiv, "❌ Error de conexión con el servidor", "danger");
    }
}

// ====================================================================
// FUNCIONES AUXILIARES
// ====================================================================
function updateStatistics(totalModelos, lowStockCount, totalSeriales) {
    animateCounter(totalItems, totalModelos);
    animateCounter(lowStockItems, lowStockCount);
    animateCounter(totalValue, totalSeriales);
}

function animateCounter(element, targetValue) {
    if (!element || typeof anime === 'undefined') {
        if (element) element.textContent = targetValue;
        return;
    }
    
    const currentValue = parseInt(element.textContent) || 0;
    anime({
        targets: { value: currentValue },
        value: targetValue,
        duration: 800,
        easing: 'easeOutQuad',
        update: function(anim) {
            element.textContent = Math.floor(anim.animations[0].currentValue);
        }
    });
}

function showMessage(element, message, type) {
    if (!element) return;
    
    element.textContent = message;
    element.className = `alert alert-${type}`;
    element.style.display = "block";
}

// ====================================================================
// EVENTOS DE MODALES Y BÚSQUEDA
// ====================================================================
if (addItemBtn) {
    addItemBtn.addEventListener("click", () => {
        console.log("➕ Abriendo modal para registrar serial...");
        if (itemModal) {
            itemModal.style.display = "flex";
        }
        if (serialForm) serialForm.reset();
        if (serialMessage) serialMessage.style.display = "none";
        if (componentTypeSelect) {
            componentTypeSelect.innerHTML = '<option value="">-- Selecciona una categoría --</option>';
        }
        if (serialProductSelect) {
            serialProductSelect.innerHTML = '<option value="">-- Selecciona un modelo (elige categoría primero) --</option>';
            serialProductSelect.disabled = true;
        }
        loadComponentTypes();
        
        if (typeof anime !== 'undefined') {
            anime({
                targets: itemModal,
                opacity: [0, 1],
                scale: [0.9, 1],
                duration: 400,
                easing: 'easeOutBack'
            });
        }
    });
}

if (productNameInput && productSKUInput) {
    productNameInput.addEventListener("input", function() {
        if (!productSKUInput.value) {
            const skuSugerido = sugerirSKU(this.value);
            productSKUInput.placeholder = `Ej: ${skuSugerido || 'SKU-PRODUCTO'}`;
        }
    });
}

function sugerirSKU(nombre) {
    if (!nombre) return '';
    
    const palabras = nombre.split(' ').filter(palabra => palabra.length > 0);
    let skuSugerido = '';
    
    for (let i = 0; i < Math.min(palabras.length, 3); i++) {
        skuSugerido += palabras[i].substring(0, 3).toUpperCase();
        if (i < Math.min(palabras.length, 3) - 1) {
            skuSugerido += '-';
        }
    }
    
    return skuSugerido;
}

if (componentTypeSelect) {
    componentTypeSelect.addEventListener('change', filterProductModels);
}

function closeModalWithAnimation(modal) {
    if (!modal) return;
    
    if (typeof anime === 'undefined') {
        anime({
            targets: modal,
            opacity: [1, 0],
            scale: [1, 0.9],
            duration: 300,
            easing: 'easeInQuad',
            complete: function() {
                modal.style.display = "none";
            }
        });
    } else {
        modal.style.display = "none";
    }
}

if (closeModal) {
    closeModal.addEventListener("click", () => {
        closeModalWithAnimation(itemModal);
    });
}

if (closeProductModal) {
    closeProductModal.addEventListener("click", () => {
        closeModalWithAnimation(productModal);
    });
}

if (newCategoryBtn) {
    newCategoryBtn.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        console.log("🆕 Botón Nueva Categoría clickeado");
        if (categoryModal) {
            categoryModal.style.display = "flex";
        }
        if (categoryForm) categoryForm.reset();
        if (categoryMessage) categoryMessage.style.display = "none";
        
        if (typeof anime !== 'undefined') {
            anime({
                targets: categoryModal,
                opacity: [0, 1],
                scale: [0.9, 1],
                duration: 400,
                easing: 'easeOutBack'
            });
        }
    });
}

if (closeCategoryModal) {
    closeCategoryModal.addEventListener("click", () => {
        closeModalWithAnimation(categoryModal);
    });
}

if (closeSerialsModal) {
    closeSerialsModal.addEventListener("click", () => {
        closeModalWithAnimation(serialsDetailModal);
    });
}

if (closeStatusModal) {
    closeStatusModal.addEventListener("click", () => {
        closeModalWithAnimation(changeStatusModal);
    });
}

// Cerrar modal al hacer click fuera
window.addEventListener("click", (e) => {
    if (itemModal && e.target == itemModal) closeModalWithAnimation(itemModal);
    if (productModal && e.target == productModal) closeModalWithAnimation(productModal);
    if (serialsDetailModal && e.target == serialsDetailModal) closeModalWithAnimation(serialsDetailModal);
    if (changeStatusModal && e.target == changeStatusModal) closeModalWithAnimation(changeStatusModal);
    if (categoryModal && e.target == categoryModal) closeModalWithAnimation(categoryModal);
});

if (searchBtn) {
    searchBtn.addEventListener("click", () => {
        loadProductosDetallados(searchInput ? searchInput.value : "");
    });
}

if (searchInput) {
    searchInput.addEventListener("keyup", (e) => {
        if (e.key === "Enter") {
            loadProductosDetallados(searchInput.value);
        }
    });

    searchInput.addEventListener("input", (e) => {
        if (e.target.value === "") {
            loadProductosDetallados("");
        }
    });
}

// Registrar nueva categoría
if (categoryForm) {
    categoryForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        if (categoryMessage) categoryMessage.style.display = "none";

        const categoryName = categoryNameInput ? categoryNameInput.value.trim() : "";

        if (!categoryName) {
            showMessage(categoryMessage, "❌ Por favor ingresa el nombre de la categoría", "danger");
            return;
        }

        try {
            const response = await secureFetch(`${INVENTARIO_URL}/tipos_pieza`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ tipo_modelo: categoryName }),
            });

            const result = await response.json();

            if (response.ok) {
                showMessage(categoryMessage, `✅ ${result.mensaje}`, "success");
                if (categoryForm) categoryForm.reset();
                
                productTypesCache = null;
                loadProductTypes();
                loadComponentTypes();
                
                setTimeout(() => {
                    if (categoryModal) categoryModal.style.display = "none";
                }, 1500);
            } else {
                showMessage(categoryMessage, `❌ ${result.error}`, "danger");
            }
        } catch (error) {
            console.error("Error:", error);
            showMessage(categoryMessage, "❌ Error al crear la categoría", "danger");
        }
    });
}

// ====================================================================
// INICIALIZACIÓN SEGURA
// ====================================================================
document.addEventListener("DOMContentLoaded", async () => {
    console.log("🚀 DOM completamente cargado");
    
    // Verificar elementos críticos
    const domOk = verificarElementosDOM();
    if (!domOk) {
        console.error("❌ Elementos DOM críticos faltantes");
        alert("Error crítico: Algunos elementos necesarios no se encontraron. Recarga la página.");
        return;
    }
    
    // Asegurar que solo el login esté visible inicialmente
    if (loginScreen) {
        loginScreen.classList.add('active');
        loginScreen.style.display = 'flex';
    }
    
    if (dashboardScreen) {
        dashboardScreen.classList.remove('active');
        dashboardScreen.style.display = 'none';
    }
    
    // Inicializar animaciones
    initializeAnimations();
    
    // Verificar sesión existente
    console.log("🔍 Verificando sesión existente...");
    try {
        const hasSession = await checkSession();
        
        if (hasSession && currentUser) {
            console.log("✅ Sesión encontrada, cargando dashboard...");
            // Mostrar dashboard directamente
            if (loginScreen) {
                loginScreen.classList.remove('active');
                loginScreen.style.display = 'none';
            }
            if (dashboardScreen) {
                dashboardScreen.classList.add('active');
                dashboardScreen.style.display = 'flex';
            }
            
            // Cargar datos del dashboard
            userInitial.textContent = currentUser.name.charAt(0);
            userName.textContent = currentUser.name;
            
            loadProductosDetallados();
            loadComponentTypes();
            animateDashboardLogo();
            startSessionChecker();
        } else {
            console.log("❌ No hay sesión activa, mostrando login");
            // Asegurar que solo el login esté visible
            if (loginScreen) {
                loginScreen.classList.add('active');
                loginScreen.style.display = 'flex';
            }
            if (dashboardScreen) {
                dashboardScreen.classList.remove('active');
                dashboardScreen.style.display = 'none';
            }
        }
    } catch (error) {
        console.error("❌ Error verificando sesión:", error);
        // Por seguridad, mostrar login
        if (loginScreen) {
            loginScreen.style.display = 'flex';
            loginScreen.classList.add('active');
        }
    }
    
    console.log("✅ Sistema de inventario para Soluciones Lógicas inicializado correctamente");
});

// ====================================================================
// AGREGAR ESTILOS CSS DINÁMICOS
// ====================================================================
const dynamicStyles = `
<style>
/* ESTILOS PARA NUEVA TABLA */
.producto-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.producto-modelo {
    font-weight: 600;
    color: var(--color-text);
}

.producto-categoria {
    font-size: 11px;
    color: var(--color-text-secondary);
    background: rgba(255, 255, 255, 0.05);
    padding: 2px 6px;
    border-radius: 10px;
    display: inline-block;
}

.sku-display {
    font-family: 'Courier New', monospace;
    font-size: 13px;
    background: rgba(255, 255, 255, 0.03);
    padding: 4px 8px;
    border-radius: 6px;
    border: 1px solid var(--color-border);
}

/* ESTILOS PARA STOCK */
.stock-display {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    border-radius: var(--border-radius-sm);
    cursor: pointer;
    transition: var(--transition);
    min-width: 140px;
    position: relative;
    border: 2px solid;
}

.stock-display:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-soft);
}

.stock-agotado {
    background: rgba(255, 68, 68, 0.1);
    border-color: rgba(255, 68, 68, 0.3);
    color: var(--color-danger);
}

.stock-bajo {
    background: rgba(255, 170, 0, 0.1);
    border-color: rgba(255, 170, 0, 0.3);
    color: var(--color-warning);
}

.stock-medio {
    background: rgba(255, 255, 0, 0.1);
    border-color: rgba(255, 255, 0, 0.3);
    color: #ffcc00;
}

.stock-normal {
    background: rgba(0, 255, 136, 0.1);
    border-color: rgba(0, 255, 136, 0.3);
    color: var(--color-success);
}

.stock-icon {
    font-size: 16px;
    font-weight: bold;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: currentColor;
    color: white;
}

.stock-agotado .stock-icon { background: var(--color-danger); }
.stock-bajo .stock-icon { background: var(--color-warning); }
.stock-medio .stock-icon { background: #ffcc00; }
.stock-normal .stock-icon { background: var(--color-success); }

.stock-number {
    font-size: 22px;
    font-weight: 800;
    min-width: 30px;
    text-align: center;
}

.stock-label {
    font-size: 11px;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stock-eye {
    margin-left: auto;
    opacity: 0.6;
    transition: opacity 0.3s;
}

.stock-display:hover .stock-eye {
    opacity: 1;
    transform: scale(1.1);
}

.stock-detalle {
    margin-top: 4px;
    font-size: 11px;
    color: var(--color-text-secondary);
    text-align: center;
}

/* DISTRIBUCIÓN DE ESTADOS */
.distribucion-estados {
    display: flex;
    flex-direction: column;
    gap: 6px;
    max-width: 200px;
}

.estado-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    width: fit-content;
    transition: var(--transition);
}

.estado-badge:hover {
    transform: translateX(3px);
}

.estado-almacen {
    background: rgba(0, 255, 136, 0.15);
    color: var(--color-success);
    border: 1px solid rgba(0, 255, 136, 0.3);
}

.estado-instalado {
    background: rgba(0, 102, 255, 0.15);
    color: var(--color-primary);
    border: 1px solid rgba(0, 102, 255, 0.3);
}

.estado-danado {
    background: rgba(255, 68, 68, 0.15);
    color: var(--color-danger);
    border: 1px solid rgba(255, 68, 68, 0.3);
}

.estado-retirado {
    background: rgba(255, 170, 0, 0.15);
    color: var(--color-warning);
    border: 1px solid rgba(255, 170, 0, 0.3);
}

/* ACCIONES RÁPIDAS */
.acciones-rapidas {
    display: flex;
    gap: 6px;
    justify-content: center;
}

.btn-accion {
    width: 36px;
    height: 36px;
    border-radius: var(--border-radius-sm);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
    font-size: 14px;
}

.btn-accion:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-soft);
}

.btn-agregar {
    background: rgba(0, 255, 136, 0.1);
    color: var(--color-success);
    border: 1px solid rgba(0, 255, 136, 0.2);
}

.btn-agregar:hover {
    background: rgba(0, 255, 136, 0.2);
}

.btn-editar {
    background: rgba(0, 102, 255, 0.1);
    color: var(--color-primary);
    border: 1px solid rgba(0, 102, 255, 0.2);
}

.btn-editar:hover {
    background: rgba(0, 102, 255, 0.2);
}

.btn-eliminar {
    background: rgba(255, 68, 68, 0.1);
    color: var(--color-danger);
    border: 1px solid rgba(255, 68, 68, 0.2);
}

.btn-eliminar:hover {
    background: rgba(255, 68, 68, 0.2);
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .stock-display {
        min-width: 120px;
        padding: 8px;
    }
    
    .stock-number {
        font-size: 18px;
    }
    
    .distribucion-estados {
        max-width: 150px;
    }
    
    .estado-badge {
        font-size: 11px;
        padding: 3px 8px;
    }
    
    .btn-accion {
        width: 32px;
        height: 32px;
    }
}

/* ESTILOS EXISTENTES MANTENIDOS */
.loading {
    border: 3px solid #f3f3f3;
    border-top: 3px solid var(--color-primary);
    border-radius: 50%;
    width: 30px;
    height: 30px;
    animation: spin 1s linear infinite;
    margin: 0 auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.login-btn {
    width: 100%;
    padding: 18px 30px;
    background: var(--color-gradient);
    color: white;
    border: none;
    border-radius: var(--border-radius);
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: var(--transition);
    text-transform: uppercase;
    letter-spacing: 1px;
    position: relative;
    overflow: hidden;
    margin-top: 10px;
}

.login-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.6s;
}

.login-btn:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-medium);
    background: var(--color-gradient-hover);
}

.login-btn:hover::before {
    left: 100%;
}

.login-btn:active {
    transform: translateY(-1px);
}

.login-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

/* ESTILOS PARA BOTONES DE ACCIÓN EN SERIALES */
.action-buttons {
    display: flex;
    gap: 8px;
    justify-content: center;
}

.btn-action {
    width: 36px;
    height: 36px;
    border-radius: var(--border-radius-sm);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
    font-size: 14px;
}

.btn-action:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-soft);
}

.change-status {
    background: rgba(0, 102, 255, 0.1);
    color: var(--color-primary);
    border: 1px solid rgba(0, 102, 255, 0.2);
}

.change-status:hover {
    background: rgba(0, 102, 255, 0.2);
}

.delete-serial {
    background: rgba(255, 68, 68, 0.1);
    color: var(--color-danger);
    border: 1px solid rgba(255, 68, 68, 0.2);
}

.delete-serial:hover {
    background: rgba(255, 68, 68, 0.2);
}
</style>
`;

document.head.insertAdjacentHTML('beforeend', dynamicStyles);

// ====================================================================
// FUNCIONALIDAD DE REPORTE DE IMPRESIÓN
// ====================================================================

// Elemento del botón de reporte
const printReportBtn = document.getElementById("printReportBtn");

// Event listener para el botón de imprimir reporte
if (printReportBtn) {
    printReportBtn.addEventListener("click", async () => {
        console.log("🖨️ Generando reporte para impresión...");
        await generarReporteCompleto();
    });
}

async function generarReporteCompleto() {
    try {
        console.log("🖨️ Iniciando generación de reporte...");
        
        // Mostrar loading en el botón
        const originalText = printReportBtn.innerHTML;
        printReportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
        printReportBtn.disabled = true;
        
        // Obtener datos del reporte
        console.log("📡 Haciendo request a:", `${INVENTARIO_URL}/reporte/completo`);
        const response = await secureFetch(`${INVENTARIO_URL}/reporte/completo`);
        
        console.log("📨 Respuesta recibida:", response.status, response.statusText);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error("❌ Error en respuesta:", errorText);
            throw new Error(`Error HTTP: ${response.status} - ${errorText}`);
        }
        
        const reporteData = await response.json();
        console.log("📊 Datos del reporte obtenidos:", reporteData);
        console.log("📈 Estadísticas:", reporteData.estadisticas);
        console.log("📦 Productos:", reporteData.productos?.length || 0);
        
        // Verificar que tenemos datos
        if (!reporteData.productos || reporteData.productos.length === 0) {
            console.warn("⚠️ No hay productos en el reporte");
            alert("No hay productos para mostrar en el reporte. Asegúrate de tener productos registrados.");
            return;
        }
        
        // Llenar el reporte HTML
        console.log("🎨 Llenando HTML del reporte...");
        llenarReporteHTML(reporteData);
        
        // Verificar que el HTML se llenó
        const printReport = document.getElementById('printReport');
        if (printReport) {
            console.log("✅ Elemento de reporte encontrado");
            console.log("📄 Contenido del reporte:", printReport.innerHTML.length, "caracteres");
        } else {
            console.error("❌ Elemento de reporte no encontrado");
            return;
        }
        
        // Imprimir después de un delay
        console.log("🖨️ Iniciando impresión en 1 segundo...");
        setTimeout(() => {
            window.print();
        }, 1000);
        
    } catch (error) {
        console.error("❌ Error generando reporte:", error);
        alert(`Error al generar el reporte: ${error.message}`);
    } finally {
        // Restaurar botón
        if (printReportBtn) {
            printReportBtn.innerHTML = '<i class="fas fa-print"></i> Imprimir Reporte';
            printReportBtn.disabled = false;
        }
    }
}

function llenarReporteHTML(data) {
    console.log("🎨 Iniciando llenado de HTML...");
    
    // Llenar fecha y usuario
    const now = new Date();
    const fechaFormateada = now.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    console.log("📅 Fecha formateada:", fechaFormateada);
    
    const printDateEl = document.getElementById('printDate');
    const printUserEl = document.getElementById('printUser');
    const printFooterDateEl = document.getElementById('printFooterDate');
    
    if (printDateEl) {
        printDateEl.textContent = fechaFormateada;
        console.log("✅ Fecha establecida");
    } else {
        console.error("❌ Elemento printDate no encontrado");
    }
    
    if (printUserEl) {
        printUserEl.textContent = data.usuario || 'Sistema';
        console.log("✅ Usuario establecido:", data.usuario);
    } else {
        console.error("❌ Elemento printUser no encontrado");
    }
    
    if (printFooterDateEl) {
        printFooterDateEl.textContent = fechaFormateada;
        console.log("✅ Fecha footer establecida");
    } else {
        console.error("❌ Elemento printFooterDate no encontrado");
    }
    
    // Llenar estadísticas
    const printStats = document.getElementById('printStats');
    if (printStats && data.estadisticas) {
        console.log("📊 Llenando estadísticas...");
        printStats.innerHTML = `
            <div class="print-stat">
                <div class="print-stat-value">${data.estadisticas.total_productos || 0}</div>
                <div class="print-stat-label">Total Productos</div>
            </div>
            <div class="print-stat">
                <div class="print-stat-value">${data.estadisticas.total_seriales || 0}</div>
                <div class="print-stat-label">Total Seriales</div>
            </div>
            <div class="print-stat">
                <div class="print-stat-value">${data.estadisticas.total_almacen || 0}</div>
                <div class="print-stat-label">En Almacén</div>
            </div>
            <div class="print-stat">
                <div class="print-stat-value">${data.estadisticas.total_instalado || 0}</div>
                <div class="print-stat-label">Instalados</div>
            </div>
            <div class="print-stat">
                <div class="print-stat-value">${data.estadisticas.total_danado || 0}</div>
                <div class="print-stat-label">Dañados</div>
            </div>
            <div class="print-stat">
                <div class="print-stat-value">${data.estadisticas.total_retirado || 0}</div>
                <div class="print-stat-label">Retirados</div>
            </div>
        `;
        console.log("✅ Estadísticas llenadas");
    } else {
        console.error("❌ Elemento printStats no encontrado o sin datos");
    }
    
    // Llenar tabla de productos
    const printTableBody = document.getElementById('printTableBody');
    if (printTableBody && data.productos) {
        console.log(`📦 Llenando tabla con ${data.productos.length} productos...`);
        printTableBody.innerHTML = '';
        
        if (data.productos.length === 0) {
            printTableBody.innerHTML = `
                <tr>
                    <td colspan="9" style="text-align: center; padding: 20px;">
                        No hay productos registrados en el sistema
                    </td>
                </tr>
            `;
            console.log("⚠️ No hay productos para mostrar");
        } else {
            data.productos.forEach((producto, index) => {
                const row = document.createElement('tr');
                
                // Procesar seriales para mostrar solo los códigos
                let serialesTexto = 'Sin seriales';
                if (producto.seriales_detalle) {
                    // Extraer solo los códigos de serial (antes del paréntesis)
                    const seriales = producto.seriales_detalle.split(', ').map(s => {
                        const codigo = s.split(' (')[0];
                        return codigo;
                    }).join(', ');
                    serialesTexto = seriales.length > 100 ? seriales.substring(0, 100) + '...' : seriales;
                }
                
                row.innerHTML = `
                    <td>${producto.marca || 'N/A'}</td>
                    <td>${producto.modelo || producto.nombre}</td>
                    <td>${producto.codigo_sku}</td>
                    <td>${producto.total_unidades || 0}</td>
                    <td>${producto.en_almacen || 0}</td>
                    <td>${producto.instalados || 0}</td>
                    <td>${producto.danados || 0}</td>
                    <td>${producto.retirados || 0}</td>
                    <td class="seriales-list">${serialesTexto}</td>
                `;
                
                printTableBody.appendChild(row);
                
                if (index === 0) {
                    console.log("📝 Primer producto agregado:", producto.nombre);
                }
            });
            console.log(`✅ ${data.productos.length} productos agregados a la tabla`);
        }
    } else {
        console.error("❌ Elemento printTableBody no encontrado o sin productos");
    }
    
    // Verificar que el elemento de reporte esté visible
    const printReport = document.getElementById('printReport');
    if (printReport) {
        console.log("📄 Estado del elemento de reporte:");
        console.log("   - Display:", window.getComputedStyle(printReport).display);
        console.log("   - Visibility:", window.getComputedStyle(printReport).visibility);
        console.log("   - Contenido length:", printReport.innerHTML.length);
    }
    
    console.log("✅ Reporte HTML llenado correctamente");
}

// Exportar funciones globales para acceso desde HTML
window.eliminarProducto = eliminarProducto;
window.mostrarSerialesDetalle = mostrarSerialesDetalle;
window.agregarStockMultiple = agregarStockMultiple;
window.cerrarModal = cerrarModal;
window.confirmarCambioEstado = confirmarCambioEstado;
window.editarProducto = editarProducto;
window.guardarCambiosProducto = guardarCambiosProducto;
window.generarReporteCompleto = generarReporteCompleto;