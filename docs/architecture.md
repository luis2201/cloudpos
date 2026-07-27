# Arquitectura inicial de CloudPOS

## Enfoque

CloudPOS es un monolito modular: un despliegue Laravel, una base MySQL y transacciones ACID entre los dominios. La separación interna evita que controladores o vistas acumulen reglas de negocio y permite evolucionar cada módulo sin introducir la complejidad operativa de microservicios.

## Límites de dominio previstos

| Dominio | Responsabilidad inicial |
| --- | --- |
| Sales | Cotización, venta, detalle, descuentos, pagos y anulaciones |
| Cash | Apertura, movimientos, arqueo, cierre y diferencias por turno |
| Inventory | Existencias por bodega, kárdex, ajustes, transferencias y políticas de saldo |
| Catalog | Categorías, marcas, unidades, presentaciones, formas de pago y productos |
| Income | Ingresos monetarios no originados por ventas |
| Expenses | Gastos, categorías, medios de pago y documentos de respaldo |
| Tax | Tarifas con vigencia, cálculo y referencia normativa |
| Access | Usuarios, roles, permisos y trazabilidad de acceso |
| Organization | Empresa, establecimientos y bodegas |
| Setup | Estado y secuencia obligatoria de configuración inicial |
| Reporting | Consultas operativas y fiscales sin alterar transacciones |

## Decisiones vigentes

1. Los importes monetarios se almacenarán como `decimal`, nunca como flotantes.
2. Cada venta guardará subtotal, descuentos, perfil tributario, porcentaje referencial, IVA desglosado y total. Bajo RIMPE Negocio Popular el IVA desglosado será cero y el comprobante conservará la leyenda del régimen; si el perfil cambia, la venta guardará la instantánea numérica fiscal aplicable en esa fecha.
3. Inventario funcionará mediante movimientos de kárdex. La existencia será una proyección verificable, no un número modificado sin rastro.
4. Una caja pertenece a un establecimiento; cada turno tendrá apertura y cierre y no podrá recibir movimientos después de cerrarse.
5. Las anulaciones generarán contramovimientos y auditoría; no se borrarán ventas, movimientos de caja ni kárdex.
6. Fechas de negocio y vigencias usan `America/Guayaquil`; los timestamps técnicos conservan el manejo estándar de Laravel.
7. Parámetros fiscales son históricos y apend-only en la operación normal. Una nueva normativa genera una nueva vigencia.
8. La instalación exige administrador, empresa, matriz y bodega principal, en ese orden. Las rutas operativas permanecen bloqueadas mientras falte un paso.
9. El primer usuario recibe el rol de sistema `Administrador`; ese rol otorga acceso total, no puede editarse y debe permanecer al menos un administrador activo.
10. Los permisos se verifican en el servidor mediante Gates de Laravel. Ocultar elementos de navegación es solo una representación adicional del mismo control.
11. La organización inicial es de una empresa por instalación. Establecimientos y bodegas pertenecen a esa empresa; una bodega pertenece además a un establecimiento.
12. No se permite stock negativo en la bodega principal de forma predeterminada.
13. Los catálogos maestros se desactivan; no se eliminan desde la interfaz. Esto conserva las referencias necesarias para ventas e inventario futuros.
14. Una presentación describe su contenido mediante cantidad y unidad. La conversión que una presentación tendrá para un producto concreto se definirá al modelar productos.
15. Las formas de pago internas se vinculan al catálogo oficial SRI. Los registros oficiales son protegidos y las variantes operativas pueden compartir el mismo código tributario.
16. Las categorías aceptan jerarquía, pero el dominio impide ciclos y la desactivación de un padre que conserve hijos activos.
17. Los precios base de producto se almacenan antes de cualquier cálculo tributario. Cada empaque de venta conserva su propio precio y código de barras.
18. La empresa opera como persona natural RIMPE Negocio Popular, comercializadora minorista, no obligada a llevar contabilidad y sin fabricación ni importación directa. Las ventas acogidas al régimen no desglosan IVA, aunque el producto conserva su clasificación fiscal y la tarifa general mantiene vigencias históricas.
19. Las existencias se expresarán en unidades de producto. Una presentación cuya unidad de contenido sea `Cantidad` aporta automáticamente su cantidad como factor de conversión; por ejemplo, caja 12 × 5 = 60 unidades.
20. La presentación principal permanece activa y todo producto debe nacer con al menos una presentación y un precio neto.
21. Las bebidas alcohólicas no manejan fecha de caducidad en el alcance acordado.
22. El ICE no se recalcula sobre la mercadería revendida. Para fundas plásticas, la política considera el tipo de contribuyente, obligación contable, número de establecimientos activos y condición de franquicia; con el perfil actual no aplica.
23. El régimen tributario de la empresa es histórico y append-only. Cada perfil guarda fecha de inicio, régimen, tipo de contribuyente, obligación contable, franquicia y respaldo; no se sobrescribe el perfil anterior.
24. `CompanyTaxProfileResolver` resuelve el perfil por fecha de negocio. Al programar RIMPE Emprendedor, las operaciones anteriores siguen bajo Negocio Popular y el desglose de IVA comienza únicamente desde la nueva vigencia.
25. La semilla comercial crea productos y empaques, pero no existencias. Incorpora una lista neta inicial; solo completa precios que continúen en cero y nunca sobrescribe valores operativos ya ajustados.
26. Para el perfil minorista actual, el ICE de la bebida es una referencia recibida en compra y no se vuelve a calcular ni sumar en la venta. Un cambio futuro a fabricante o importador requiere habilitar un módulo tributario directo antes de vender bajo ese perfil.
27. Cada empaque separa el código comercial del fabricante de su Code 128 interno. Los GTIN/EAN/UPC numéricos validan el dígito de control; el identificador interno es de circulación cerrada, se genera sin reemplazar al original y se representa como SVG imprimible.
28. Una presentación utilizada por productos conserva inmutables la cantidad y la unidad de contenido. Esto evita que editar el catálogo cambie indirectamente factores de inventario ya registrados; los maestros se desactivan en lugar de eliminarse.
29. La existencia es una proyección única por bodega y producto, expresada en unidades base. El kárdex es la fuente auditable y cada línea conserva saldos anterior y posterior.
30. Operaciones y movimientos de inventario son inmutables en la aplicación y en la base de datos. Una corrección se registra mediante una nueva entrada, salida o ajuste compensatorio; nunca se reescribe el historial.
31. Una transferencia se confirma en una sola transacción y genera exactamente dos movimientos: salida de la bodega de origen y entrada en la bodega de destino. La cantidad de empaques y su factor de conversión quedan guardados como instantánea.
32. La carga inicial solo se admite antes del primer movimiento de cada producto en cada bodega. Puede combinar varias presentaciones del mismo producto dentro de una sola operación atómica; cada presentación genera una línea con su conversión histórica. Una vez iniciado el kárdex deben utilizarse entradas o ajustes.
33. La regla de stock negativo pertenece a cada bodega y se evalúa con bloqueo de la proyección dentro de la transacción. Una bodega que lo prohíbe rechaza salidas y transferencias que dejen saldo menor que cero.
34. Una bodega con existencias distintas de cero no puede desactivarse. Si contiene saldos negativos tampoco puede cambiarse su política para prohibir negativos hasta regularizarlos.

## Siguientes decisiones necesarias

- Tipo de comprobantes y alcance de facturación electrónica con SRI.
- Integrar la referencia ICE y los componentes de trazabilidad recibidos del proveedor en el futuro módulo de compras.
- Regla de redondeo tributario por línea y por comprobante.
- Puntos de emisión por establecimiento y secuenciales por tipo de comprobante.
- Roles operativos iniciales y autorizaciones específicas para descuentos, anulaciones y ajustes.
- Métodos de pago, ventas a crédito y manejo de clientes.

Estas decisiones deben cerrarse antes de modelar ventas y facturación definitivas.
