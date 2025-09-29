# Verifica INPI – Fabrik Cron Plugin

Este documento descreve as regras, funcionalidades e finalidade do plugin **Verifica INPI**, desenvolvido para o componente **Fabrik** no Joomla, como parte das soluções criadas para a plataforma **PITT**.  

> **Importante:** Este plugin amplia a gama de recursos do Fabrik, adicionando automação via **cron jobs** para monitoramento de publicações do **Instituto Nacional da Propriedade Industrial (INPI)**.

---

## 📜 Visão Geral

O **Verifica INPI** é um plugin de cron do Fabrik que realiza buscas periódicas nas revistas publicadas pelo INPI, disponíveis no endereço:  

> [http://revistas.inpi.gov.br/txt/*](http://revistas.inpi.gov.br/txt/*)

A cada execução, o plugin:  
- Analisa o conteúdo das revistas do INPI.  
- Identifica patentes previamente registradas na base local do sistema.  
- Verifica se as patentes foram citadas em novas publicações.  
- Gera **alertas automáticos** indicando se é necessária alguma ação por parte do pesquisador responsável.  

Dessa forma, pesquisadores podem acompanhar de maneira ágil o status de suas patentes e evitar a perda de prazos ou obrigações legais.  

---

## 🔄 Critérios de Execução

O plugin será disparado de acordo com o intervalo configurado no Fabrik.  
Cada execução realiza as seguintes etapas:  

1. Consulta às revistas do INPI.  
2. Extração e leitura das informações publicadas.  
3. Comparação com as patentes cadastradas localmente.  
4. Criação de registros internos (via `registro.php`).  
5. Emissão de alertas (via `alerta.php`) quando necessário.  

---

## 📌 Observações

- O plugin foi desenvolvido especificamente para a **plataforma PITT**.  
- É indispensável manter o cadastro atualizado das patentes de interesse para que o monitoramento seja eficaz.  
