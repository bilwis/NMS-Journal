<?php
	require_once ('libs/autoload.php');
	require_once ('db.php');

	class System {
		public $uuid;
		public $orig_name;
		public $name;
		public $spectral_class;
		public $water;
		public $region_uuid;
		public $lifeform;
		public $econ_wealth;
		public $econ_wealth_str;
		public $econ_type;
		public $econ_type_str;
		public $conflict;
		public $conflict_str;
		public $planets;
		public $moons;
		public $special;
		public $discovery_date;
		public $discoverer;
		
		function System(
			$orig_name,
			$name,
			$spectral_class,
			$water,
			$region_uuid,
			$lifeform,
			$econ_wealth_str,
			$econ_type_str,
			$conflict_str,
			$planets,
			$moons,
			$special,
			$discovery_date,
			$discoverer
		) {
			$this->uuid = Ramsey\Uuid\Uuid::uuid1();
			$this->orig_name = $orig_name;
			$this->name = $name;
			$this->spectral_class = $spectral_class;
			$this->water = $water;
			$this->econ_wealth_str = $econ_wealth_str;
			$this->econ_type_str = $econ_type_str;
			$this->conflict_str = $conflict_str;
			$this->planets = $planets;
			$this->moons = $moons;
			$this->special = $special;
			$this->discovery_date = $discovery_date;
			$this->discoverer = $discoverer;		
		}
		
		function getEconomyType($synonym)
		{
			return "None";
		}
		
		function getEconomyWealth($synonym)
		{
			return "None";
		}
		
		function getConflictLevel($synonym)
		{
			return "None";
		}
	}

/*
	class SystemSynonym
	{
		public $text;
		public $type;
		
		function SystemSynonym ($text, $type)
		{
			$this->text = $text;
			
		}
		
	}

	class SpectralClass
	{
		public $class;
		public $color;
	}
	*/

?>