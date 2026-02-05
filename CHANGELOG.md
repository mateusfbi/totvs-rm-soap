# Changelog

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

## [1.1.0] - 2026-02-05
### Adicionado
- Método `setXMLFromArray` em `DataServer` para facilitar a criação de XML a partir de arrays.
- Método `getXML` em `DataServer` para visualizar o XML gerado.

### Corrigido
- Removida a redeclaração da propriedade `$webService` nas classes de serviço para corrigir erro fatal de tipagem.
- Ajustes de compatibilidade de tipos no PHP 8.