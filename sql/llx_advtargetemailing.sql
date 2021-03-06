-- ============================================================================
-- Copyright (C) 2013 Florian Henry <florian.henry@open-concept.pro>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- Table of "Plan de comptes" for accountancy expert module
-- ============================================================================

CREATE TABLE IF NOT EXISTS llx_advtargetemailing
(
  rowid integer NOT NULL auto_increment PRIMARY KEY,
  name varchar(200) NOT NULL,
  entity integer NOT NULL DEFAULT 1,
  fk_mailing	integer NOT NULL,
  filtervalue	text,
  fk_user_author integer NOT NULL,
  datec datetime NOT NULL,
  fk_user_mod integer NOT NULL,
  tms timestamp NOT NULL
)ENGINE=InnoDB;
