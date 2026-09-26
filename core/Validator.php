<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Validateur de données.
 *
 * Fournit une API simple et chaînable pour valider les données
 * provenant de formulaires ou de requêtes HTTP. Chaque règle est
 * une méthode qui enregistre une validation pour un champ donné.
 *
 * Exemple :
 *   $validator = new Validator($data);
 *   $validator->required('email');
 *   $validator->email('email');
 *   $validator->minLength('password', 8);
 *
 *   if ($validator->fails()) {
 *       $errors = $validator->errors();
 *   }
 *
 * Les messages d'erreur sont en français et associés aux noms
 * de champ. Un champ peut avoir plusieurs erreurs en interne,
 * mais errors() retourne la première erreur pour chaque champ.
 *
 * @package App\Core
 */
class Validator
{
    /**
     * Données à valider.
     *
     * @var array<string, mixed>
     */
    protected array $data;

    /**
     * Erreurs de validation.
     *
     * Structure interne : [champ => [message1, message2, ...]]
     *
     * @var array<string, array<int, string>>
     */
    protected array $errors = [];

    /**
     * Messages d'erreur par défaut en français.
     *
     * Les placeholders :attribute, :min, :max sont remplacés
     * par les valeurs réelles lors de la génération du message.
     *
     * @var array<string, string>
     */
    protected array $messages = [
        'required'  => 'Le champ :attribute est obligatoire.',
        'email'     => 'Le champ :attribute doit être une adresse email valide.',
        'minLength' => 'Le champ :attribute doit contenir au moins :min caractères.',
        'maxLength' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
        'confirmed' => 'La confirmation de :attribute ne correspond pas.',
        'integer'   => 'Le champ :attribute doit être un nombre entier.',
        'numeric'   => 'Le champ :attribute doit être une valeur numérique.',
        'in'        => 'Le champ :attribute contient une valeur non autorisée.',
        'url'       => 'Le champ :attribute doit être une URL valide.',
    ];

    /**
     * Labels personnalisés pour les champs.
     *
     * @var array<string, string>
     */
    protected array $labels = [];

    /**
     * Constructeur.
     *
     * @param array<string, mixed> $data Données à valider
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Définit un label personnalisé pour un champ.
     *
     * @param string $field Nom du champ
     * @param string $label Label lisible (ex: 'mot de passe')
     * @return self Instance courante pour chaînage
     */
    public function setLabel(string $field, string $label): self
    {
        $this->labels[$field] = $label;
        return $this;
    }

    /**
     * Définit plusieurs labels personnalisés d'un coup.
     *
     * @param array<string, string> $labels Tableau [champ => label]
     * @return self Instance courante pour chaînage
     */
    public function setLabels(array $labels): self
    {
        foreach ($labels as $field => $label) {
            $this->labels[$field] = $label;
        }
        return $this;
    }

    /**
     * Règle : champ obligatoire.
     *
     * Échoue si le champ est absent, null, chaîne vide ou tableau vide.
     * La valeur 0 (int) et "0" (string) sont considérées comme valides.
     *
     * @param string $field Nom du champ
     * @return self Instance courante pour chaînage
     */
    public function required(string $field): self
    {
        $value = $this->value($field);

        if ($this->isEmpty($value)) {
            $this->addError($field, 'required', $field);
        }

        return $this;
    }

    /**
     * Règle : adresse email valide.
     *
     * La règle est ignorée si le champ est absent, null ou vide
     * (utilisez required() en complément pour l'obligation).
     *
     * @param string $field Nom du champ
     * @return self Instance courante pour chaînage
     */
    public function email(string $field): self
    {
        $value = $this->value($field);

        if ($this->shouldSkip($value)) {
            return $this;
        }

        // Vérifie que la valeur est une chaîne et une adresse email valide
        if (!is_string($value) || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, 'email', $field);
        }

        return $this;
    }

    /**
     * Règle : longueur minimale (UTF-8).
     *
     * Utilise mb_strlen() si l'extension mbstring est disponible,
     * sinon strlen() en secours. Les caractères accentués français
     * comptent pour un seul caractère.
     *
     * La règle est ignorée si le champ est absent, null ou vide.
     *
     * @param string $field Nom du champ
     * @param int $min Longueur minimale
     * @return self Instance courante pour chaînage
     */
    public function minLength(string $field, int $min): self
    {
        $value = $this->value($field);

        if ($this->shouldSkip($value)) {
            return $this;
        }

        $length = $this->stringLength((string)$value);

        if ($length < $min) {
            $this->addError($field, 'minLength', $field, ['min' => $min]);
        }

        return $this;
    }

    /**
     * Règle : longueur maximale (UTF-8).
     *
     * Utilise mb_strlen() si l'extension mbstring est disponible,
     * sinon strlen() en secours.
     *
     * La règle est ignorée si le champ est absent, null ou vide.
     *
     * @param string $field Nom du champ
     * @param int $max Longueur maximale
     * @return self Instance courante pour chaînage
     */
    public function maxLength(string $field, int $max): self
    {
        $value = $this->value($field);

        if ($this->shouldSkip($value)) {
            return $this;
        }

        $length = $this->stringLength((string)$value);

        if ($length > $max) {
            $this->addError($field, 'maxLength', $field, ['max' => $max]);
        }

        return $this;
    }

    /**
     * Règle : confirmation de champ.
     *
     * Vérifie que le champ {field}_confirmation correspond exactement
     * à la valeur du champ {field}. Convention : suffixe "_confirmation".
     *
     * Exemple : $validator->confirmed('password');
     *   → compare 'password' avec 'password_confirmation'
     *
     * @param string $field Nom du champ
     * @return self Instance courante pour chaînage
     */
    public function confirmed(string $field): self
    {
        $value = $this->value($field);

        if ($this->shouldSkip($value)) {
            return $this;
        }

        $confirmationField = $field . '_confirmation';
        $confirmation = $this->value($confirmationField);

        // Échoue si la confirmation est absente ou différente
        if ($this->isEmpty($confirmation) || $confirmation !== $value) {
            $this->addError($field, 'confirmed', $field);
        }

        return $this;
    }

    /**
     * Règle : nombre entier.
     *
     * Accepte les entiers PHP, les chaînes numériques entières
     * ("42", "-7") et rejette les nombres décimaux, les booléens,
     * les tableaux et les valeurs non numériques.
     *
     * La règle est ignorée si le champ est absent, null ou vide.
     *
     * @param string $field Nom du champ
     * @return self Instance courante pour chaînage
     */
    public function integer(string $field): self
    {
        $value = $this->value($field);

        if ($this->shouldSkip($value)) {
            return $this;
        }

        // Vérifie le type selon la nature de la valeur
        $isValid = false;

        if (is_int($value)) {
            $isValid = true;
        } elseif (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            $isValid = true;
        }

        if (!$isValid) {
            $this->addError($field, 'integer', $field);
        }

        return $this;
    }

    /**
     * Règle : valeur numérique.
     *
     * Accepte les entiers, les flottants et les chaînes numériques.
     * Rejette les booléens, les tableaux et les valeurs non numériques.
     *
     * La règle est ignorée si le champ est absent, null ou vide.
     *
     * @param string $field Nom du champ
     * @return self Instance courante pour chaînage
     */
    public function numeric(string $field): self
    {
        $value = $this->value($field);

        if ($this->shouldSkip($value)) {
            return $this;
        }

        // Vérifie le type selon la nature de la valeur
        $isValid = false;

        if (is_int($value) || is_float($value)) {
            $isValid = true;
        } elseif (is_string($value) && is_numeric($value)) {
            $isValid = true;
        }

        if (!$isValid) {
            $this->addError($field, 'numeric', $field);
        }

        return $this;
    }

    /**
     * Règle : valeur dans une liste autorisée.
     *
     * La comparaison est stricte (===) : "1" et 1 sont distincts
     * si la liste contient uniquement des entiers.
     *
     * La règle est ignorée si le champ est absent, null ou vide.
     *
     * @param string $field Nom du champ
     * @param array<int, mixed> $allowedValues Liste des valeurs autorisées
     * @return self Instance courante pour chaînage
     */
    public function in(string $field, array $allowedValues): self
    {
        $value = $this->value($field);

        if ($this->shouldSkip($value)) {
            return $this;
        }

        // Vérifie l'appartenance à la liste autorisée
        if (!in_array($value, $allowedValues, true)) {
            $this->addError($field, 'in', $field);
        }

        return $this;
    }

    /**
     * Règle : URL valide.
     *
     * Vérifie que la valeur est une URL syntaxiquement valide.
     *
     * La règle est ignorée si le champ est absent, null ou vide.
     *
     * @param string $field Nom du champ
     * @return self Instance courante pour chaînage
     */
    public function url(string $field): self
    {
        $value = $this->value($field);

        if ($this->shouldSkip($value)) {
            return $this;
        }

        // Vérifie que la valeur est une chaîne et une URL valide
        if (!is_string($value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
            $this->addError($field, 'url', $field);
        }

        return $this;
    }

    /**
     * Vérifie si la validation a échoué.
     *
     * @return bool True si au moins une erreur a été détectée
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Vérifie si la validation a réussi.
     *
     * @return bool True si aucune erreur n'a été détectée
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Retourne les erreurs de validation.
     *
     * Structure : [champ => premier_message]
     * Un seul message par champ est retourné pour rester simple.
     *
     * @return array<string, string> Erreurs associées aux champs
     */
    public function errors(): array
    {
        $result = [];

        foreach ($this->errors as $field => $messages) {
            $result[$field] = $messages[0];
        }

        return $result;
    }

    /**
     * Vérifie si un champ a des erreurs.
     *
     * @param string $field Nom du champ
     * @return bool True si le champ a au moins une erreur
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    /**
     * Retourne la première erreur d'un champ.
     *
     * @param string $field Nom du champ
     * @return string|null Message d'erreur ou null si aucune erreur
     */
    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Récupère la valeur d'un champ.
     *
     * Retourne null si le champ n'existe pas dans les données,
     * ce qui permet aux règles de s'exécuter sans avertissement.
     *
     * @param string $field Nom du champ
     * @return mixed Valeur du champ ou null
     */
    protected function value(string $field): mixed
    {
        return $this->data[$field] ?? null;
    }

    /**
     * Vérifie si une valeur doit être ignorée par la règle.
     *
     * La plupart des règles (sauf required) sont ignorées si
     * le champ est absent, null ou vide. Cela permet de valider
     * uniquement les champs réellement fournis.
     *
     * @param mixed $value Valeur à vérifier
     * @return bool True si la valeur doit être ignorée
     */
    protected function shouldSkip(mixed $value): bool
    {
        return $this->isEmpty($value);
    }

    /**
     * Vérifie si une valeur est considérée comme "vide".
     *
     * Est considéré vide :
     *   - null
     *   - chaîne vide ""
     *   - tableau vide []
     *   - chaîne ne contenant que des espaces
     *
     * N'est PAS considéré vide :
     *   - 0 (int)
     *   - 0.0 (float)
     *   - "0" (string)
     *   - false (bool)
     *
     * @param mixed $value Valeur à vérifier
     * @return bool True si la valeur est vide
     */
    protected function isEmpty(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            return count($value) === 0;
        }

        return false;
    }

    /**
     * Calcule la longueur d'une chaîne en UTF-8.
     *
     * Utilise mb_strlen() si l'extension mbstring est disponible.
     * Les caractères accentués français (é, è, ê, à, ç...) comptent
     * pour un seul caractère.
     *
     * @param string $value Chaîne à mesurer
     * @return int Longueur de la chaîne
     */
    protected function stringLength(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value, 'UTF-8');
        }

        // Fallback : la longueur octets peut sous-compter les accents
        // mais évite une erreur fatale si mbstring est absent.
        return strlen($value);
    }

    /**
     * Ajoute une erreur pour un champ.
     *
     * Le message est construit à partir des messages par défaut
     * avec remplacement des placeholders :attribute, :min, :max.
     *
     * @param string $field Nom du champ
     * @param string $rule Nom de la règle
     * @param string $attribute Nom du champ pour le message
     * @param array<string, int|string> $params Paramètres supplémentaires (min, max...)
     * @return void
     */
    protected function addError(string $field, string $rule, string $attribute, array $params = []): void
    {
        // Message de base pour la règle
        $message = $this->messages[$rule] ?? 'Le champ :attribute est invalide.';

        // Remplace :attribute par le label lisible du champ
        $label = $this->labels[$attribute] ?? $this->humanize($attribute);
        $message = str_replace(':attribute', $label, $message);

        // Remplace les paramètres numériques (:min, :max)
        foreach ($params as $key => $value) {
            $message = str_replace(':' . $key, (string)$value, $message);
        }

        // Ajoute le message à la liste des erreurs du champ
        $this->errors[$field][] = $message;
    }

    /**
     * Humanise un nom de champ.
     *
     * Transforme "prenom" → "prenom", "mot_de_passe" → "mot de passe",
     * "confirm_password" → "confirm password".
     *
     * @param string $field Nom du champ
     * @return string Version lisible du champ
     */
    protected function humanize(string $field): string
    {
        return str_replace('_', ' ', $field);
    }
}