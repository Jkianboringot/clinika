<?php

/*
|--------------------------------------------------------------------------
| Medical questionnaire
|--------------------------------------------------------------------------
| Single source of truth: the patient form, the validation and the dentist
| view all read from here. key => label. Keys are what get stored, so don't
| rename a key once patients have answered (labels can be edited freely).
*/

return [

    // YES / NO questions
    'general' => [
        'pain_now'           => 'Are you having pain or discomfort this time?',
        'nervous'            => 'Do you feel very nervous about having dental treatment?',
        'bad_experience'     => 'Have you ever had a bad experience in the dental office?',
        'hospitalized'       => 'Have you ever been a patient in the hospital during the past two years?',
        'medication'         => 'Have you taken any medicine or drugs during the past two years?',
        'drug_allergy'       => 'Are you allergic to or made sick by any drugs or medications?',
        'excessive_bleeding' => 'Have you ever had any excessive bleeding requiring special treatment?',
        'stop_walking'       => 'When you go up stairs or take a walk, do you ever stop?',
        'chest_pain'         => 'Do you feel pain in your chest? Shortness of breath, or because you are very tired?',
        'ankles_swell'       => 'Do your ankles swell during the day?',
        'pillows'            => 'Do you use more than two pillows to sleep?',
        'weight_change'      => 'Have you lost or gained more than 10lbs in the past years?',
        'wake_breathless'    => 'Do you ever wake up from sleep out of breath?',
        'special_diet'       => 'Are you on a special diet?',
        'cancer'             => 'Has your medical doctor ever said you have a cancer or tumor?',
        'other_condition'    => 'Do you have any disease, condition, or problem that is not listed? (Refer to the list below)',
    ],

    // Only shown to female patients
    'women' => [
        'pregnant'      => 'Are you pregnant right now?',
        'menstruating'  => 'Are you menstruating?',
        'breast_feeding' => 'Are you breast feeding?',
    ],

    // "Check if any of the following which you have had or have at the present"
    'conditions' => [
        'heart_failure'        => 'Heart Failure',
        'heart_disease'        => 'Heart Disease or Attack',
        'high_blood_pressure'  => 'High Blood Pressure',
        'heart_murmur'         => 'Heart Murmur',
        'rheumatic_fever'      => 'Rheumatic Fever',
        'scarlet_fever'        => 'Scarlet Fever',
        'drug_addiction'       => 'Drug Addiction',
        'angina_pectoris'      => 'Angina Pectoris',
        'fainting'             => 'Fainting or Dizzy Spells',
        'heart_surgery'        => 'Heart Surgery',
        'anemia'               => 'Anemia',
        'stroke'               => 'Stroke',
        'kidney_trouble'       => 'Kidney Trouble',
        'ulcer'                => 'Ulcer',
        'emphysema'            => 'Emphysema',
        'cough'                => 'Cough',
        'hemophilia'           => 'Hemophilia',
        'nervousness'          => 'Nervousness',
        'sickle_cell'          => 'Sickle Cell Disease',
        'tuberculosis'         => 'Tuberculosis',
        'asthma'               => 'Asthma',
        'hay_fever'            => 'Hay Fever',
        'sinus_trouble'        => 'Sinus Trouble',
        'allergies_hive'       => 'Allergies or Hive',
        'diabetes'             => 'Diabetes',
        'thyroid_disease'      => 'Thyroid Disease',
        'cold_sore'            => 'Cold Sore',
        'psychiatric_treatment' => 'Psychiatric Treatment',
        'bruise_easily'        => 'Bruise Easily',
        'rheumatism'           => 'Rheumatism',
        'glaucoma'             => 'Glaucoma',
        'jaw_joint_pain'       => 'Pain in Jaw Joints',
        'aids'                 => 'AIDS',
        'hepatitis'            => 'Hepatitis',
        'liver_disease'        => 'Liver Disease',
        'blood_transfusion'    => 'Blood Transfusion',
        'epilepsy'             => 'Epilepsy or Seizures',
    ],
];