<?php

use Phinx\Migration\AbstractMigration;

final class AddEnumsConstraintsForSalesAndPaymentMethods extends AbstractMigration
{
    public function up(): void
    {
        // Normalize legacy values (seeds or existing data)
        $this->execute("UPDATE payment_methods SET type = 'ELECTRONIC' WHERE LOWER(type) IN ('electronic')");
        $this->execute("UPDATE payment_methods SET type = 'CASH' WHERE LOWER(type) IN ('physical', 'cash', 'money', 'dinheiro')");

        $this->execute("UPDATE sales SET status = 'OPEN' WHERE LOWER(status) IN ('open', 'aberta')");
        $this->execute("UPDATE sales SET status = 'FINALIZED' WHERE LOWER(status) IN ('finalized', 'finalizada', 'finished', 'closed')");

        // Constraints
        $this->execute("
            ALTER TABLE payment_methods
            ADD CONSTRAINT payment_methods_type_check
            CHECK (type IN ('ELECTRONIC', 'CASH'))
        ");

        $this->execute("
            ALTER TABLE sales
            ADD CONSTRAINT sales_status_check
            CHECK (status IN ('OPEN', 'FINALIZED'))
        ");

        // Defaults
        $this->execute("ALTER TABLE sales ALTER COLUMN status SET DEFAULT 'OPEN'");
    }

    public function down(): void
    {
        $this->execute("ALTER TABLE payment_methods DROP CONSTRAINT IF EXISTS payment_methods_type_check");
        $this->execute("ALTER TABLE sales DROP CONSTRAINT IF EXISTS sales_status_check");
        $this->execute("ALTER TABLE sales ALTER COLUMN status SET DEFAULT 'open'");
    }
}

