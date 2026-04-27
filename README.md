# 🛒 Sales System - Solução de Gestão de Vendas

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php)
![Vue.js](https://img.shields.io/badge/Vue.js-3.0-4FC08D?style=for-the-badge&logo=vue.js)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15-336791?style=for-the-badge&logo=postgresql)
![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker)

Um sistema completo de gestão de vendas focado em robustez, manutenibilidade e boas práticas de arquitetura de software. Desenvolvido para atender aos requisitos técnicos de avaliação, abrangendo autenticação, controle de clientes e produtos, ciclo de vida complexo de vendas (múltiplos pagamentos e cálculo de troco) e emissão de recibos em PDF.

---

## 🏗️ Arquitetura e Design Patterns

Para demonstrar proficiência além do uso básico de frameworks, o backend foi construído com **Slim 4**, focando na explicitação dos fluxos, sem "mágicas" de ORMs complexos.

1. **Service Layer Pattern**: As regras de negócio não ficam nos Controllers. Todas as lógicas (ex: calcular troco, finalizar venda) vivem em Services (`SaleService`, `PaymentMethodService`).
2. **Repository Pattern**: O acesso ao banco (PostgreSQL) foi encapsulado em repositórios usando **PDO puro**. Isso permite total controle sobre as *queries* e facilita a injeção de dependências para testes.
3. **Dependency Injection (DI)**: Uso nativo de injeção de dependências pelo construtor (via PHP-DI), garantindo baixo acoplamento e altíssima testabilidade.
4. **Single Responsibility Principle (SOLID)**: Separação clara entre quem roteia (Routes), quem trata requisição (Controllers), quem processa regras (Services/Calculators) e quem acessa dados (Repositories).

### 💡 Regras de Negócio Críticas Aplicadas

- **Snapshot de Preços**: Ao adicionar um produto à venda, o `unit_price` é copiado para a tabela `itens_venda`. Se o preço do produto alterar amanhã, o histórico da venda permanecerá financeiramente intacto.
- **Transações Atômicas (ACID)**: A finalização da venda (`addPayment` e `finalize`) opera sob blocos `BEGIN` e `COMMIT`. Se a inserção de um pagamento falhar, ocorre o `ROLLBACK`, evitando estados inconsistentes de "venda parcialmente paga no banco".
- **Isolamento Numérico**: A lógica matemática de troco foi separada em uma classe Pura (`SaleCalculator`), garantindo que os testes unitários possam varrer dezenas de cenários (falta dinheiro, troco exato, múltiplos métodos mistos) em milissegundos sem tocar em banco de dados.

---

## 🚀 Como Executar o Projeto (Local)

O projeto é 100% conteinerizado. Siga os passos:

```bash
# 1. Clone o repositório e crie o arquivo de variáveis de ambiente
cp .env.example .env

# 2. Suba os containers (O banco será populado com as migrations e seeders automaticamente)
docker compose up -d --build
```

**Acessos:**
- **Frontend Vue.js**: [http://localhost:8080](http://localhost:8080)
- **Backend API**: `http://localhost:8000`
- **Credenciais de Teste (Seeder)**: `admin@email.com` / `123456`

---

## 🧪 Testes e Qualidade

O foco dos testes unitários foi colocado onde realmente importa: **Nas regras de negócio.**

Para executar a suíte do PHPUnit via Docker:
```bash
docker compose exec php vendor/bin/phpunit
```

*(Opcional) Como rodar as migrations manualmente se necessário:*
```bash
docker compose exec php vendor/bin/phinx migrate
```

---

## 📚 Documentação da API (Postman)

Uma collection robusta do Postman foi criada e está localizada em `/postman/sales-system-backend.postman_collection.json`. 
Ela possui **scripts automáticos de teste** e passagem de variáveis de ambiente (captura o `token` de login e IDs criados para usar nas rotas subsequentes).
Para testar, basta importar o JSON no seu Postman e rodar as requisições em sequência.

Também fornecemos um [Diagrama de Fluxo UML](DIAGRAMA_UML_FLUXO.puml) documentando o estado da venda do início ao fim (geração do PDF).

---

## ⚖️ Trade-offs e Próximos Passos (Visão de Longo Prazo)

Se o escopo contemplasse o ambiente de produção para milhares de usuários, algumas melhorias seriam aplicadas:
1. **Fila para o PDF (Message Broker)**: A geração de PDF via `dompdf` é síncrona. Em alta escala, isso bloquearia o servidor PHP. O correto seria disparar um evento (RabbitMQ/Redis) e gerar assincronamente.
2. **Tipagem Decimal Absoluta**: Atualmente o banco usa `NUMERIC(10,2)`. Para sistemas financeiros de altíssima precisão, usar o padrão *Integer para Centavos* ou a extensão `bcmath` evitaria o temido problema de "ponto flutuante" do PHP em cenários muito complexos.
3. **Padrão DTO e Value Objects**: Para garantir ainda mais tipagem na entrada de dados entre o Controller e a camada de Service.
