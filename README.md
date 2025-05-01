# WP SRI Security
## Descripción
WP SRI Security es un plugin de WordPress que implementa automáticamente atributos SRI (Subresource Integrity) para recursos externos cargados en tu sitio web. Esta característica de seguridad ayuda a proteger tu sitio contra ataques de inyección de código malicioso en recursos de terceros.

## ¿Qué es SRI?
Subresource Integrity (SRI) es una característica de seguridad que permite a los navegadores verificar que los recursos cargados desde servidores externos (como CDNs) no han sido manipulados. Funciona añadiendo un hash criptográfico a las etiquetas <script> y <link> que el navegador verifica antes de ejecutar el contenido.

## Características Principales
- Implementación Automática : Añade atributos SRI a scripts y hojas de estilo externos sin necesidad de configuración manual.
- Escaneo Inteligente : Detecta automáticamente recursos externos en tu sitio.
- Algoritmos Seguros : Soporta los algoritmos de hash SHA-256, SHA-384 y SHA-512.
- Caché Optimizada : Almacena los hashes generados para mejorar el rendimiento.
- Panel de Administración : Interfaz intuitiva para gestionar y monitorear recursos externos.
- Exclusiones Personalizables : Permite excluir dominios específicos del procesamiento SRI.
## Beneficios de Seguridad
- Protege contra ataques Man-in-the-Middle en recursos de terceros.
- Previene la ejecución de scripts modificados maliciosamente.
- Añade una capa adicional de seguridad a tu sitio WordPress.
- Cumple con las mejores prácticas de seguridad web recomendadas por OWASP.
## Instalación
1. Sube la carpeta wp-sri-sec al directorio /wp-content/plugins/ de tu instalación WordPress.
2. Activa el plugin a través del menú 'Plugins' en WordPress.
3. Accede a la configuración desde el menú 'WP SRI Security' en el panel de administración.
## Configuración
- Habilitar para Scripts : Activa/desactiva SRI para archivos JavaScript.
- Habilitar para Estilos : Activa/desactiva SRI para archivos CSS.
- Algoritmo de Hash : Selecciona el algoritmo de hash preferido (sha256, sha384, sha512).
- Dominios a Excluir : Lista de dominios que no serán procesados por el plugin.
## Requisitos
- WordPress 5.0 o superior
- PHP 7.0 o superior
- Permisos para crear tablas en la base de datos
## Compatibilidad
El plugin es compatible con la mayoría de los temas y plugins de WordPress. Sin embargo, algunos recursos dinámicos o cargados mediante JavaScript podrían requerir configuración adicional.

## Soporte Técnico
Para soporte técnico, preguntas o sugerencias, por favor contacta a través de:

- GitHub: https://github.com/wilcastel
- Sitio web: https://wilcastell.com/wp-sri-security
## Licencia
Este plugin está licenciado bajo GPL-2.0+.

## Contribuciones
Las contribuciones son bienvenidas. Si deseas contribuir al desarrollo de este plugin, por favor visita nuestro repositorio en GitHub.

## Estructura del Plugin
El plugin sigue las mejores prácticas de WordPress, con un archivo principal que define constantes, carga dependencias y maneja la activación. Las clases están organizadas en archivos separados (includes/), lo que facilita el mantenimiento y extensibilidad.

## Notas de Seguridad
Este plugin mejora la seguridad de tu sitio, pero no reemplaza otras medidas de seguridad importantes como mantener WordPress y sus plugins actualizados, usar contraseñas fuertes y limitar los intentos de inicio de sesión.

## == Changelog ==

= 0.0.2 =
* Se modificó la estructura de la base de datos, para permitir la actualización o cambio de algoritmos de hash.
* Se agregó mas opciones de casos en el método get_or_create_resource.
* Se corrige El error "Cannot use object of type stdClass as array" en resource_needs_update
