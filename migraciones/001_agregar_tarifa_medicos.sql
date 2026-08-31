ALTER TABLE medicos
  ADD COLUMN tarifa DOUBLE NOT NULL DEFAULT 0 AFTER estado;

UPDATE medicos SET tarifa = 0 WHERE tarifa IS NULL;
