<?php

/**
 * EntryLevel.php - Entry-level template facade
 *
 * Aggregates all entry-level templates from individual classes into a single facade
 * to match the JavaScript entryLevel.js pattern where all templates are exported
 * from a single module.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Jerry Padgett <sjpadgett@gmail.com>
 * @copyright Copyright (c) 2026 Jerry Padgett <sjpadgett@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Carecoordination\Model\PhpCcdaBuilder\Templates\EntryLevel;

/**
 * Entry-level template facade
 *
 * All public static methods return array<string, mixed> template definitions.
 *
 * @method static array<string, mixed> allergyProblemAct()
 * @method static array<string, mixed> allergyProblemActNKA()
 * @method static array<string, mixed> allergyIntoleranceObservation()
 * @method static array<string, mixed> allergyIntoleranceObservationNKA()
 * @method static array<string, mixed> medicationActivity()
 * @method static array<string, mixed> medicationInformation()
 * @method static array<string, mixed> problemConcernAct()
 * @method static array<string, mixed> problemObservation()
 * @method static array<string, mixed> problemStatus()
 * @method static array<string, mixed> procedureActivityAct()
 * @method static array<string, mixed> procedureActivityProcedure()
 * @method static array<string, mixed> procedureActivityObservation()
 * @method static array<string, mixed> resultOrganizer()
 * @method static array<string, mixed> resultObservation()
 * @method static array<string, mixed> vitalSignsOrganizer()
 * @method static array<string, mixed> vitalSignObservation()
 * @method static array<string, mixed> immunizationActivity()
 * @method static array<string, mixed> immunizationMedicationInformation()
 * @method static array<string, mixed> encounterActivities()
 * @method static array<string, mixed> socialHistoryObservation()
 * @method static array<string, mixed> smokingStatusObservation()
 * @method static array<string, mixed> genderStatusObservation()
 * @method static array<string, mixed> tribalAffiliationObservation()
 * @method static array<string, mixed> pregnancyStatusObservation()
 * @method static array<string, mixed> sexualOrientationObservation()
 * @method static array<string, mixed> genderIdentityObservation()
 * @method static array<string, mixed> sexObservation()
 * @method static array<string, mixed> healthConcernObservation()
 * @method static array<string, mixed> healthConcernActivityAct()
 * @method static array<string, mixed> planOfCareActivityAct()
 * @method static array<string, mixed> planOfCareActivityObservation()
 * @method static array<string, mixed> plannedProcedure()
 * @method static array<string, mixed> planOfCareActivityProcedure()
 * @method static array<string, mixed> planOfCareActivityEncounter()
 * @method static array<string, mixed> planOfCareActivitySubstanceAdministration()
 * @method static array<string, mixed> planOfCareActivitySupply()
 * @method static array<string, mixed> planOfCareActivityInstructions()
 * @method static array<string, mixed> goalActivityObservation()
 * @method static array<string, mixed> careTeamOrganizer()
 * @method static array<string, mixed> careTeamProviderAct()
 * @method static array<string, mixed> mentalStatusObservation()
 * @method static array<string, mixed> functionalStatusOrganizer()
 * @method static array<string, mixed> functionalStatusObservation()
 * @method static array<string, mixed> disabilityStatusObservation()
 * @method static array<string, mixed> advanceDirectiveObservation()
 * @method static array<string, mixed> medicalDeviceActivityProcedure()
 * @method static array<string, mixed> coverageActivity()
 * @method static array<string, mixed> severityObservation()
 * @method static array<string, mixed> reactionObservation()
 * @method static array<string, mixed> serviceDeliveryLocation()
 * @method static array<string, mixed> ageObservation()
 * @method static array<string, mixed> indication()
 * @method static array<string, mixed> instructions()
 * @method static array<string, mixed> encDiagnosis()
 * @method static array<string, mixed> notesAct()
 * @method static array<string, mixed> drugVehicle()
 * @method static array<string, mixed> preconditionForSubstanceAdministration()
 */
class EntryLevel
{
    // ============================================
    // Allergy Entry Level Templates
    // ============================================

    /**
     * @return array<string, mixed>
     */
    public static function allergyProblemAct(): array
    {
        return AllergyEntryLevel::allergyProblemAct();
    }

    /**
     * @return array<string, mixed>
     */
    public static function allergyProblemActNKA(): array
    {
        return AllergyEntryLevel::allergyProblemActNKA();
    }

    /**
     * @return array<string, mixed>
     */
    public static function allergyIntoleranceObservation(): array
    {
        return AllergyEntryLevel::allergyIntoleranceObservation();
    }

    /**
     * @return array<string, mixed>
     */
    public static function allergyIntoleranceObservationNKA(): array
    {
        return AllergyEntryLevel::allergyIntoleranceObservationNKA();
    }

    // ============================================
    // Medication Entry Level Templates
    // ============================================

    /**
     * @return array<string, mixed>
     */
    public static function medicationActivity(): array
    {
        return MedicationEntryLevel::medicationActivity();
    }

    /**
     * @return array<string, mixed>
     */
    public static function medicationInformation(): array
    {
        return MedicationEntryLevel::medicationInformation();
    }

    // ============================================
    // Problem Entry Level Templates
    // ============================================

    /**
     * @return array<string, mixed>
     */
    public static function problemConcernAct(): array
    {
        return ProblemEntryLevel::problemConcernAct();
    }

    /**
     * @return array<string, mixed>
     */
    public static function problemObservation(): array
    {
        return ProblemEntryLevel::problemObservation();
    }

    /**
     * @return array<string, mixed>
     */
    public static function problemStatus(): array
    {
        return ProblemEntryLevel::problemStatus();
    }

    // ============================================
    // Procedure Entry Level Templates
    // ============================================

    /**
     * @return array<string, mixed>
     */
    public static function procedureActivityAct(): array
    {
        return ProcedureEntryLevel::procedureActivityAct();
    }

    /**
     * @return array<string, mixed>
     */
    public static function procedureActivityProcedure(): array
    {
        return ProcedureEntryLevel::procedureActivityProcedure();
    }

    /**
     * @return array<string, mixed>
     */
    public static function procedureActivityObservation(): array
    {
        return ProcedureEntryLevel::procedureActivityObservation();
    }

    // ============================================
    // Result Entry Level Templates
    // ============================================

    /**
     * @return array<string, mixed>
     */
    public static function resultOrganizer(): array
    {
        return ResultEntryLevel::resultOrganizer();
    }

    /**
     * @return array<string, mixed>
     */
    public static function resultObservation(): array
    {
        return ResultEntryLevel::resultObservation();
    }

    // ============================================
    // Vital Sign Entry Level Templates
    // ============================================

    /**
     * @return array<string, mixed>
     */
    public static function vitalSignsOrganizer(): array
    {
        return VitalSignEntryLevel::vitalSignsOrganizer();
    }

    /**
     * @return array<string, mixed>
     */
    public static function vitalSignObservation(): array
    {
        return VitalSignEntryLevel::vitalSignObservation();
    }

    // ============================================
    // Immunization Entry Level Templates
    // ============================================

    /**
     * @return array<string, mixed>
     */
    public static function immunizationActivity(): array
    {
        return ImmunizationEntryLevel::immunizationActivity();
    }

    /**
     * @return array<string, mixed>
     */
    public static function immunizationMedicationInformation(): array
    {
        return ImmunizationEntryLevel::immunizationMedicationInformation();
    }

    // ============================================
    // Encounter Entry Level Templates
    // ============================================

    /**
     * @return array<string, mixed>
     */
    public static function encounterActivities(): array
    {
        return EncounterEntryLevel::encounterActivities();
    }

    // ============================================
    // Social History Entry Level Templates
    // ============================================

    /**
     * @return array<string, mixed>
     */
    public static function socialHistoryObservation(): array
    {
        return SocialHistoryEntryLevel::socialHistoryObservation();
    }

    /**
     * @return array<string, mixed>
     */
    public static function smokingStatusObservation(): array
    {
        return SocialHistoryEntryLevel::smokingStatusObservation();
    }

    /**
     * @return array<string, mixed>
     */
    public static function genderStatusObservation(): array
    {
        return SocialHistoryEntryLevel::genderStatusObservation();
    }

    /**
     * @return array<string, mixed>
     */
    public static function tribalAffiliationObservation(): array
    {
        return SocialHistoryEntryLevel::tribalAffiliationObservation();
    }

    /**
     * @return array<string, mixed>
     */
    public static function pregnancyStatusObservation(): array
    {
        return SocialHistoryEntryLevel::pregnancyStatusObservation();
    }

    /**
     * @return array<string, mixed>
     */
    public static function sexualOrientationObservation(): array
    {
        return SocialHistoryEntryLevel::sexualOrientationObservation();
    }

    /**
     * @return array<string, mixed>
     */
    public static function genderIdentityObservation(): array
    {
        return SocialHistoryEntryLevel::genderIdentityObservation();
    }

    /**
     * @return array<string, mixed>
     */
    public static function sexObservation(): array
    {
        return SocialHistoryEntryLevel::sexObservation();
    }

    // ============================================
    // Plan of Care Entry Level Templates
    // ============================================

    /**
     * @return array<string, mixed>
     */
    public static function healthConcernObservation(): array
    {
        return PlanOfCareEntryLevel::healthConcernObservation();
    }

    /**
     * @return array<string, mixed>
     */
    public static function healthConcernActivityAct(): array
    {
        return PlanOfCareEntryLevel::healthConcernActivityAct();
    }

    /**
     * @return array<string, mixed>
     */
    public static function planOfCareActivityAct(): array
    {
        return PlanOfCareEntryLevel::planOfCareActivityAct();
    }

    /**
     * @return array<string, mixed>
     */
    public static function planOfCareActivityObservation(): array
    {
        return PlanOfCareEntryLevel::planOfCareActivityObservation();
    }

    /**
     * @return array<string, mixed>
     */
    public static function plannedProcedure(): array
    {
        return PlanOfCareEntryLevel::plannedProcedure();
    }

    /**
     * @return array<string, mixed>
     */
    public static function planOfCareActivityProcedure(): array
    {
        return PlanOfCareEntryLevel::planOfCareActivityProcedure();
    }

    /**
     * @return array<string, mixed>
     */
    public static function planOfCareActivityEncounter(): array
    {
        return PlanOfCareEntryLevel::planOfCareActivityEncounter();
    }

    /**
     * @return array<string, mixed>
     */
    public static function planOfCareActivitySubstanceAdministration(): array
    {
        return PlanOfCareEntryLevel::planOfCareActivitySubstanceAdministration();
    }

    /**
     * @return array<string, mixed>
     */
    public static function planOfCareActivitySupply(): array
    {
        return PlanOfCareEntryLevel::planOfCareActivitySupply();
    }

    /**
     * @return array<string, mixed>
     */
    public static function planOfCareActivityInstructions(): array
    {
        return PlanOfCareEntryLevel::planOfCareActivityInstructions();
    }

    // ============================================
    // Goal Entry Level Templates
    // ============================================

    /**
     * @return array<string, mixed>
     */
    public static function goalActivityObservation(): array
    {
        return GoalEntryLevel::goalActivityObservation();
    }

    // ============================================
    // Care Team Entry Level Templates
    // ============================================

    /**
     * @return array<string, mixed>
     */
    public static function careTeamOrganizer(): array
    {
        return CareTeamEntryLevel::careTeamOrganizer();
    }

    /**
     * @return array<string, mixed>
     */
    public static function careTeamProviderAct(): array
    {
        return CareTeamEntryLevel::careTeamProviderAct();
    }

    // ============================================
    // Functional Status Entry Level Templates
    // ============================================

    /**
     * @return array<string, mixed>
     */
    public static function mentalStatusObservation(): array
    {
        return FunctionalStatusEntryLevel::mentalStatusObservation();
    }

    /**
     * @return array<string, mixed>
     */
    public static function functionalStatusOrganizer(): array
    {
        return FunctionalStatusEntryLevel::functionalStatusOrganizer();
    }

    /**
     * @return array<string, mixed>
     */
    public static function functionalStatusObservation(): array
    {
        return FunctionalStatusEntryLevel::functionalStatusObservation();
    }

    /**
     * @return array<string, mixed>
     */
    public static function disabilityStatusObservation(): array
    {
        return FunctionalStatusEntryLevel::disabilityStatusObservation();
    }

    // ============================================
    // Advance Directives Entry Level Templates
    // ============================================

    /**
     * @return array<string, mixed>
     */
    public static function advanceDirectiveObservation(): array
    {
        return AdvanceDirectivesEntryLevel::advanceDirectiveObservation();
    }

    // ============================================
    // Medical Device Entry Level Templates
    // ============================================

    /**
     * @return array<string, mixed>
     */
    public static function medicalDeviceActivityProcedure(): array
    {
        return MedicalDeviceEntryLevel::medicalDeviceActivityProcedure();
    }

    // ============================================
    // Payer Entry Level Templates
    // ============================================

    /**
     * @return array<string, mixed>
     */
    public static function coverageActivity(): array
    {
        return PayerEntryLevel::coverageActivity();
    }

    // ============================================
    // Shared Entry Level Templates (delegated)
    // ============================================

    /**
     * @return array<string, mixed>
     */
    public static function severityObservation(): array
    {
        return SharedEntryLevel::severityObservation();
    }

    /**
     * @return array<string, mixed>
     */
    public static function reactionObservation(): array
    {
        return SharedEntryLevel::reactionObservation();
    }

    /**
     * @return array<string, mixed>
     */
    public static function serviceDeliveryLocation(): array
    {
        return SharedEntryLevel::serviceDeliveryLocation();
    }

    /**
     * @return array<string, mixed>
     */
    public static function ageObservation(): array
    {
        return SharedEntryLevel::ageObservation();
    }

    /**
     * @return array<string, mixed>
     */
    public static function indication(): array
    {
        return SharedEntryLevel::indication();
    }

    /**
     * @return array<string, mixed>
     */
    public static function instructions(): array
    {
        return SharedEntryLevel::instructions();
    }

    /**
     * @return array<string, mixed>
     */
    public static function encDiagnosis(): array
    {
        return SharedEntryLevel::encDiagnosis();
    }

    /**
     * @return array<string, mixed>
     */
    public static function notesAct(): array
    {
        return SharedEntryLevel::notesAct();
    }

    /**
     * @return array<string, mixed>
     */
    public static function drugVehicle(): array
    {
        return SharedEntryLevel::drugVehicle();
    }

    /**
     * @return array<string, mixed>
     */
    public static function preconditionForSubstanceAdministration(): array
    {
        return SharedEntryLevel::preconditionForSubstanceAdministration();
    }
}
