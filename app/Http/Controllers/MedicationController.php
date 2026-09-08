<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


/**
 * =====================================================================
 * MEDICATION MODULE — Controller Notes
 * =====================================================================
 * Type   : Product — Backend Logic
 * Purpose: Manages the list of medications available for prescription.
 *          Acts as an editable "dropdown source" for the clinic —
 *          not a fixed pharmaceutical database, since not all medicine
 *          names are known ahead of time and the clinic should be able
 *          to add its own.
 * =====================================================================
 *
 * NATURE OF THIS MODULE
 * ----------------------
 * - Simple CRUD, no real business logic — except unique name check.
 * - not Stored as free text input as itis for dropdown, NOT an enum.
 *   Reason: medicine names/usage repeat often, so once created,
 *   the same entry gets reused instead of duplicated.
 *
 * DATA FIELDS
 * -----------
 * - medication_name   (e.g. "Amoxicillin")
 * - medication_dosage
 *
 * NOT INCLUDED (on purpose)
 * --------------------------
 * - frequency of use
 *   Reason: this is a clinic, not a pharmaceutical system —
 *   frequency should be tailored per patient, not fixed per medication.
 *
 * ARCHITECTURE FLOW
 * ------------------
 *   UI -> Route -> Controller -> Request Form (validation) -> Service
 *
 *   NOTE: Wrap creation logic in try/catch.
 *
 * BUSINESS LOGIC
 * ---------------
 * 1. Unique name enforcement
 *    - Medication name must be unique.
 *    - On create: check storage first.
 *      - If not found -> create.
 *      - If found -> stop immediately (return existing / reject duplicate).
 *
 * 2. [REMINDER] Delete restriction (not implemented yet)
 *    - Ideally: should not delete a medication if it's linked/referenced
 *      elsewhere (e.g. existing prescriptions).
 *    - NOT required for now — only add if explicitly requested later.
 *
 * =====================================================================
 */


class MedicationController extends Controller
{
    // simple controller like user
}
