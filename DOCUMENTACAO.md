# Documentação do Sistema - SGF (Sistema de Gerenciamento de Frota)

Este documento fornece uma visão detalhada do sistema de gerenciamento de frota "SGF".

## 1. Visão Geral
O sistema permite o controle de uma frota de veículos, registrando abastecimentos, manutenções e vinculando motoristas aos veículos. Possui dois níveis de acesso: **Administrador** e **Motorista**.

## 2. Estrutura de Pastas
O projeto está dividido em `backend` e `frontend`:

- `/backend`: Lógica de servidor em PHP.
    - `/api`: Endpoints REST que retornam e recebem JSON.
    - `/database`: Configuração de conexão e scripts de setup.
    - `/models`: Classes PHP que representam as entidades do banco de dados e contêm a lógica de CRUD.
- `/frontend`: Interface do usuário.
    - `/admin`: Páginas exclusivas para administradores.
    - `/motorista`: Páginas simplificadas para motoristas.
    - `/css`: Estilos globais.
    - `/js`: Lógica de frontend (integração com API, manipulação do DOM).

## 3. Banco de Dados (sistema-partum)
O sistema utiliza MySQL com as seguintes tabelas:

### `usuarios`
- `id`: Identificador único.
- `nome`: Nome completo do usuário.
- `email`: E-mail para login (único).
- `senha`: Senha (armazenada em texto simples ou hash conforme implementação).
- `tipo`: 'admin' ou 'motorista'.

### `veiculos`
- `id`: Identificador único.
- `placa`: Placa do veículo (única).
- `motorista_responsavel_id`: Chave estrangeira para `usuarios.id`.

### `abastecimentos`
- `id`: Identificador único.
- `veiculo_id`: FK para `veiculos.id`.
- `motorista_id`: FK para `usuarios.id`.
- `data_abastecimento`: Data do registro.
- `km_atual`: Quilometragem no momento do abastecimento.
- `litros`: Quantidade de combustível.
- `valor_total`: Custo total.
- `posto`: Nome do posto.

### `manutencoes`
- `id`: Identificador único.
- `veiculo_id`: FK para `veiculos.id`.
- `data_manutencao`: Data do serviço.
- `descricao`: Detalhes da manutenção.
- `valor_total`: Custo total.
- `km_veiculo`: Quilometragem do veículo na manutenção.
- `tipo`: 'preventiva' ou 'corretiva'.
- `realizada_por`: Oficina ou mecânico.

## 4. Fluxo de Autenticação
1. O usuário faz login em `frontend/index.html`.
2. O sistema verifica as credenciais via `backend/api/login.php`.
3. Se bem-sucedido, os dados do usuário (ID, nome, tipo) são salvos no `localStorage`.
4. O usuário é redirecionado para `/admin/index.html` ou `/motorista/index.html` baseado no seu `tipo`.
5. Scripts em `frontend/js/auth.js` garantem que apenas usuários autenticados e do tipo correto acessem as páginas protegidas.

## 5. Endpoints da API
Todos os endpoints estão em `backend/api/` e operam principalmente com métodos GET e POST.

- `usuarios.php`: Gerencia o CRUD de usuários.
- `veiculos.php`: Gerencia o CRUD de veículos.
- `abastecimentos.php`: Gerencia registros de abastecimento.
- `manutencoes.php`: Gerencia registros de manutenção.
- `login.php`: Valida credenciais.

## 6. Configuração e Instalação
1. Certifique-se de que o Apache e MySQL estão rodando (ex: XAMPP).
2. O arquivo principal de configuração de banco de dados é `backend/database/connection.php`.
3. Para criar o banco de dados e as tabelas iniciais, acesse: `http://localhost/controle-km/backend/database/setup.php`.
4. O administrador padrão é `admin@frota.com` com a senha `123123`.
