<?php

namespace Drupal\provus\Generators;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Extension\ModuleExtensionList;
use DrupalCodeGenerator\Command\BaseGenerator;
use DrupalCodeGenerator\Utils;
use Drush\Style\DrushStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Implements a Provus sub-profile generator for Drush.
 *
 * The generator will ask for a human-readable name, machine name, optional
 * description, and optional lists of modules to include in the generated
 * profile, and Provus modules to exclude.
 *
 * Leveraged from code provided by Acquia for the Lightning distribution.
 */
final class SubProfileGenerator extends BaseGenerator {

  /**
   * The module extension list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  private $moduleList;

  /**
   * {@inheritdoc}
   */
  protected $name = 'provus-subprofile';

  /**
   * {@inheritdoc}
   */
  protected $description = 'Generates a Provus Subprofile.';

  /**
   * {@inheritdoc}
   */
  protected $alias = 'psp';

  /**
   * {@inheritdoc}
   */
  protected $templatePath = __DIR__;

  /**
   * SubProfileGenerator constructor.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_list
   *   The module extension list service.
   */
  public function __construct(ModuleExtensionList $module_list) {
    parent::__construct();
    $this->moduleList = $module_list;

    // Drush doesn't support setting $this->destination to 'profiles/custom',
    // so we need to use a callback function instead. This is ignored if
    // the --directory option is set; in that case, the profile will be
    // generated at $custom_directory/$machine_name.
    // @see \Drush\Commands\generate\Helper\InputHandler::collectVars()
    // @see \Drush\Commands\generate\Helper\InputHandler::getDirectory()
    $this->setDestination(function () {
      return 'profiles/custom';
    });
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    $io = new DrushStyle($input, $output);

    $questions['name'] = new Question('Profile Name');
    $questions['name']->setValidator([Utils::class, 'validateRequired']);
    $questions['machine_name'] = new Question('Profile Machine Name (enter for default)');
    $questions['machine_name']->setValidator([Utils::class, 'validateMachineName']);
    $questions['description'] = new Question('Enter the description (optional)');
    $questions['install'] = new Question('Additional modules to include (optional), separated by commas (e.g. context, rules, file_entity)', NULL);
    $questions['install']->setNormalizer([static::class, 'toArray']);

    $vars = &$this->collectVars($input, $output, $questions);

    if ($io->confirm('Do you want to exclude any components of Provus?', FALSE)) {
      $filter = function ($name) {
        return strpos($name, 'provus_') === 0;
      };
      $modules = array_filter($this->moduleList->getAllAvailableInfo(), $filter, ARRAY_FILTER_USE_KEY);

      $map = function (array $info) {
        return $info['name'];
      };
      $modules = array_map($map, $modules);

      $questions['exclude'] = new ChoiceQuestion('Provus components to exclude (optional), entered as keys separated by commas (e.g. provus_media, provus_layout)', $modules);
      $questions['exclude']->setMultiselect(TRUE);

      $this->collectVars($input, $output, $questions);
    }

    $info_array = [
      'name' => $vars['name'],
      'type' => 'profile',
      'description' => '',
      'core_version_requirement' => '^8.8 || ^9',
      'install' => [],
      'exclude' => [
        'lightning_api',
        'lightning_contact_form',
        'lightning_roles',
        'lightning_scheduled_updates',
        'lightning_search',
      ],
      'themes' => [
        'bartik',
        'claro',
        'seven',
        'provus',
        'gin'
      ],
      'base profile' => 'provus',
      'exclude' => [],
    ];

    if ($vars['description']) {
      $info_array['description'] = $vars['description'];
    }
    if ($vars['install']) {
      $info_array['install'] = $vars['install'];
    }
    if ($vars['exclude']) {
      $info_array['exclude'] = is_string($vars['exclude'])
        ? static::toArray($vars['exclude'])
        : $vars['exclude'];
    }

    $info_array = array_filter($info_array);

    $this->addFile()
      ->path("{machine_name}/{machine_name}.info.yml")
      ->content(Yaml::encode($info_array));

    $this->addFile()
      ->path("{machine_name}/{machine_name}.install")
      ->template('install.twig');

    $this->addFile()
      ->path("{machine_name}/{machine_name}.profile")
      ->template('profile.twig');
  }

  /**
   * Converts a comma-separated string to an array of trimmed values.
   *
   * @param string $string
   *   The comma-separated string to split up.
   *
   * @return string[]
   *   The comma-separated values, trimmed of white space.
   */
  public static function toArray($string) {
    return $string ? array_map('trim', explode(',', $string)) : [];
  }

}
