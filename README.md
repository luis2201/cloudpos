# CloudPOS

Monolito de punto de venta para licorerías, construido con Laravel 13, PHP 8.4 y MySQL 8.4 LTS. La base actual incluye infraestructura, identidad visual, configuración organizacional, usuarios, roles y parametrización histórica del IVA.

## Inicio rápido con Docker

Requisitos: Docker Engine con Docker Compose.

```bash
cp .env.example .env
docker compose up --build -d
```

La aplicación queda disponible en [http://localhost:8080](http://localhost:8080). MySQL se expone en `localhost:3307` para herramientas de desarrollo. Las migraciones y datos iniciales se aplican automáticamente al iniciar el contenedor.

## Primera configuración

CloudPOS no incluye credenciales predeterminadas. En una instalación sin usuarios, la aplicación obliga a completar este orden:

1. Crear el primer usuario, asignándole automáticamente el rol protegido `Administrador`.
2. Registrar la empresa y su información tributaria.
3. Crear el establecimiento matriz.
4. Crear la bodega principal asociada a la matriz.

Hasta completar los cuatro pasos no se habilita el panel operativo. Una vez creado el primer usuario, el formulario público de registro inicial queda cerrado. Desde el panel, el administrador puede mantener empresa, establecimientos, bodegas, usuarios, contraseñas, roles y permisos.

Comandos habituales:

```bash
docker compose ps
docker compose logs -f app
docker compose exec app php artisan test
docker compose exec app php artisan migrate:status
docker compose down
```

`docker compose down` conserva la base de datos. Para usar credenciales distintas, cambia `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` y `DB_ROOT_PASSWORD` en `.env` antes del primer arranque.

## Estructura inicial

```text
app/
├── Domain/
│   ├── Access/              # Roles y permisos
│   ├── Organization/        # Empresa, establecimientos y bodegas
│   ├── Setup/               # Secuencia obligatoria de instalación
│   └── Tax/                 # Modelo y resolución de vigencias tributarias
├── Http/Controllers/        # Entrada web, sin reglas fiscales duplicadas
resources/
├── css/                     # Sistema visual CloudPOS
├── js/                      # Modales y navegación adaptable
└── views/                   # Layout, componentes y pantallas Blade
database/
├── migrations/              # Esquema versionado
└── seeders/                 # Parámetros iniciales idempotentes
docker/                      # Arranque del contenedor Laravel
compose.yaml                 # Aplicación y MySQL dentro del proyecto cloudpos
```

Los límites previstos del monolito son: ventas, ingresos, caja, inventario, gastos, catálogo, tributación, identidad y reportes. Se mantendrán dentro de una sola aplicación y una sola base de datos, pero con reglas de negocio separadas por dominio.

## IVA con vigencia histórica

La tarifa no se guarda como una constante global. Cada valor tiene una fecha `effective_from`, y `TaxRateResolver` elige la última tarifa cuya vigencia sea anterior o igual a la fecha consultada. No se editan registros anteriores para cambiar la tarifa; se agrega una nueva vigencia.

La semilla inicial registra 15% desde el 1 de abril de 2024, con referencia al Decreto Ejecutivo No. 198, según el alcance acordado. Antes de usar el sistema en producción se debe contrastar la tarifa, el régimen del contribuyente y las reglas particulares de productos con el [Servicio de Rentas Internas](https://www.sri.gob.ec/impuesto-al-valor-agregado-iva). La parametrización permite incorporar un cambio futuro sin recalcular ventas históricas.

## Línea visual

El logo original se conserva en la raíz y se publica en `public/images/logo-cloudpos.png`. La interfaz deriva de sus colores principales:

- Azul profundo `#071D49` para navegación y jerarquía.
- Azul eléctrico `#075FEB` para acciones y estados informativos.
- Naranja `#FF6A0A` para acentos y llamados de atención.
- Fondos claros y tipografía Instrument Sans para lectura prolongada.

Las pantallas ya incluyen paneles, cards, formularios organizados, alertas modales, tablas adaptables, filtros, ordenamiento y paginación reutilizable.

## Datos tributarios

La instalación registra a la empresa como persona natural acogida al RIMPE Negocio Popular, no obligada a llevar contabilidad, comercializadora minorista y no importadora. El formulario impide combinar Negocio Popular con una sociedad, obligación contable, agente de retención o contribuyente especial. El RUC se valida por formato numérico de 13 dígitos y unicidad. No se aplica un algoritmo universal inventado: el propio SRI advierte que ciertos RUC no siguen el algoritmo de validación habitual, por lo que la situación real debe verificarse contra sus servicios oficiales.

Los cambios de régimen se registran en `company_tax_profiles` como vigencias históricas. Desde Datos de la empresa se puede programar RIMPE Emprendedor, régimen general u otra vigencia posterior indicando fecha, respaldo legal, obligación contable y condición de franquicia. Los perfiles anteriores no se editan: `CompanyTaxProfileResolver` elige el último registro vigente para la fecha de la operación y `CompanyTaxPolicy` determina desde ese perfil si corresponde desglosar IVA y si puede aplicar ICE a fundas plásticas.

## Catálogo base

El permiso `catalog.manage` habilita una pantalla unificada con filtros, ordenamiento, paginación y mantenimiento de:

- Categorías jerárquicas, con protección frente a ciclos y desactivación de padres con hijas activas.
- Marcas comerciales.
- Unidades de cantidad, volumen y peso, indicando si aceptan decimales.
- Presentaciones con cantidad y unidad de contenido, por ejemplo botella de 750 ml o caja de 12 unidades.
- Formas de pago operativas vinculadas a un código oficial del SRI.

La semilla inicial incluye 21 categorías, 31 marcas, unidades y presentaciones usuales para una licorería. También incorpora las ocho formas de pago vigentes del catálogo SRI (`01`, `15`–`21`) como registros protegidos. Su código y nombre oficial no pueden alterarse, aunque sí se puede configurar si están activos, si requieren referencia y si afectan el efectivo de caja. Las formas personalizadas se mapean a uno de estos códigos oficiales.

El acceso directo `Presentaciones` permite crear, buscar, ordenar, paginar y editar formatos comerciales. No existe eliminación física: se usa desactivación controlada. Una vez que una presentación está asociada a un producto, su cantidad y unidad quedan bloqueadas para no modificar retroactivamente el factor de inventario; nombre y descripción permanecen editables.

## Productos y empaques

Cada producto registra código interno, categoría, marca, tipo, grado alcohólico y los tratamientos de IVA e ICE que le corresponden. Las bebidas alcohólicas no manejan fecha de caducidad.

El precio base se guarda por presentación antes de cualquier cálculo tributario. Un mismo producto puede tener botella, six pack y caja, cada uno con código de barras y precio independientes.

La semilla comercial agrega 30 productos de referencia y 69 empaques de venta entre cervezas, whiskies, ron, vodka, tequila, ginebra, licores, vinos, gaseosas y energizantes. Incluye precios netos iniciales editables y descuentos referenciales de 5% para six packs, 8% para cajas de 12 y 10% para cajas de 24. Solo completa precios que continúen en cero, por lo que una nueva ejecución no sobrescribe valores ajustados por el negocio. Esta carga no crea existencias ni movimientos de inventario.

Cada empaque admite el GTIN/EAN/UPC impreso por el fabricante y valida su dígito de control cuando corresponde. CloudPOS genera adicionalmente un identificador Code 128 interno, único dentro de la instalación, que puede abrirse como SVG para imprimir etiquetas. El código interno no se presenta como GTIN ni reemplaza el código comercial: [GS1 Ecuador](https://gs1ec.org/contenido/solicitar-codigos-de-barras/) es la entidad que asigna identificadores globales para marcas propias.

El inventario se contabiliza en unidades. La conversión se deriva de la presentación: una caja de 12 representa 12 unidades y cinco cajas representan 60. Esta regla está centralizada en `InventoryUnitConverter` para que entradas, transferencias y ventas utilicen exactamente el mismo cálculo.

La instalación inicia como persona natural RIMPE Negocio Popular. La clasificación de IVA del producto se conserva, pero las ventas acogidas a esa vigencia no desglosan IVA y su cuota anual comprende este impuesto. Si entra en vigencia un perfil RIMPE Emprendedor, desde esa fecha el POS utilizará la tarifa histórica de IVA del producto sin modificar operaciones anteriores. El ICE generado previamente por el fabricante o importador no se recalcula al revender la mercadería; su clasificación y código se conservan como referencia de compra y trazabilidad. El [SRI identifica la fabricación, producción, elaboración o importación](https://www.sri.gob.ec/o/sri-portlet-biblioteca-alfresco-internet/descargar/8be598b6-9808-4cbb-97af-c30312cb98e4/Preguntas%20frecuentes.pdf) como las actividades que generan las obligaciones directas de ICE para estos bienes.

El perfil actual, no obligado a llevar contabilidad y con menos de tres establecimientos, no genera ICE por entrega de fundas plásticas. `CompanyTaxPolicy` vuelve a evaluar esta regla según los establecimientos activos y considera la excepción de franquicia, para la cual el ICE puede aplicar independientemente del número de locales.

## Inventario

El módulo mantiene existencias independientes por bodega y producto, siempre expresadas en unidades base. Una misma carga inicial puede combinar varias presentaciones del producto y aplica el factor de cada línea; por ejemplo, 10 cajas de 12 más 3 unidades ingresan 123 unidades. La operación solo puede realizarse mientras el producto no tenga movimientos en esa bodega. Las reposiciones posteriores se registran como entradas o ajustes, nunca sobrescribiendo el historial.

El kárdex registra operaciones de carga inicial, entrada, salida, ajuste y transferencia. Una carga inicial mixta genera una sola operación y una línea por presentación, con saldos consecutivos hasta alcanzar el total. Cada movimiento conserva saldo anterior, variación, saldo resultante y, cuando interviene una presentación, una instantánea de la cantidad de empaques y unidades por empaque. Los ajustes reciben el conteo físico total y contabilizan únicamente la diferencia. Una transferencia es una sola operación atómica con una salida de origen y una entrada de destino.

Las operaciones y líneas del kárdex son inmutables: la aplicación no ofrece edición ni eliminación, los modelos las bloquean y la base de datos añade triggers que rechazan cambios directos. Toda corrección debe crear un movimiento compensatorio. El saldo negativo se valida dentro de la misma transacción según la política `allow_negative_stock` de cada bodega. Tampoco se permite desactivar una bodega con existencias distintas de cero ni deshabilitar su política de negativos mientras conserve saldos negativos.

La semilla de productos no inventa existencias. El negocio debe registrar su carga inicial desde [Inventario](http://localhost:8080/inventario) para cada bodega.

## Verificación

```bash
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint --test
```

Consulta las decisiones y el mapa de evolución en [docs/architecture.md](docs/architecture.md).
