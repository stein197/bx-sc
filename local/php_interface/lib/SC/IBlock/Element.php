<?php
	namespace SC\IBlock;

	use \CIBlockElement;
	use \CFile;

	class Element extends Entity {

		use Parentable;
		use Propertiable;

		public function save(): void {
			$celement = new CIBlockElement;
			$arFields = array_merge($this->arFields, ['PROPERTY_VALUES' => $this->getProperties()]);
			if ($this->id) {
				$result = $celement->Update($this->id, $arFields);
			} else {
				$result = $celement->Add($arFields);
				if ($result)
					$this->id = $result;
			}
			if (!$result)
				throw new Exception($celement->LAST_ERROR);
		}

		public function delete(): void {
			if (!$this->id)
				return;
			if (CIBlockElement::delete($this->id)) {
				$this->id = null;
				unset($this->arFields['ID']);
			} else {
				throw new Exception;
			}
		}

		protected function fetchFields(): void {
			$this->arFields = CIBlockElement::GetByID($this->id)->GetNext();
			$this->arFields['PREVIEW_PICTURE'] = CFile::GetFileArray($this->arFields['PREVIEW_PICTURE']);
			$this->arFields['DETAIL_PICTURE'] = CFile::GetFileArray($this->arFields['DETAIL_PICTURE']);
		}

		public static function getList(array $arFilter, array $arOrder = ['SORT' => 'ASC'], ?array $arSelect = null, ?array $arNav = null): array {
			$rs = CIBlockElement::GetList($arOrder, $arFilter, false, $arNav, $arSelect);
			$result = [];
			while ($o = $rs->GetNextElement()) {
				$f = $o->GetFields();
				$f['PROPERTIES'] = $o->GetProperties();
				$result[] = $f;
			}
			return $result;
		}

		public static function getByID(int $id, bool $onlyStub = false): ?Element {
			$o = null;
			if ($onlyStub) {
				$o = new self;
				$o->id = $id;
			} else {
				$arFields = CIBlockElement::GetByID($id)->GetNext();
				if ($arFields)
					$o = self::wrap($arFields);
			}
			return $o;
		}
		
		protected function fetchProperties(): void {
			$this->arProperties = [];
			$rs = CIBlockElement::GetProperty($this->id, $this->getField('IBLOCK_ID'));
			while ($ar = $rs->Fetch())
				$this->arProperties[$ar['CODE']] = $ar;
		}

		public function getParents(): array {
			global $DB;
			$rs = $DB->Query("SELECT IBLOCK_SECTION_ID FROM b_iblock_section_element WHERE IBLOCK_ELEMENT_ID = {$this->id}");
			$result = [];
			while ($ar = $rs->Fetch())
				$result[] = (int) $ar['IBLOCK_SECTION_ID'];
			return $result;
		}

		// TODO
		public function setParents(): array {}

		public static function wrap(array $arFields): Element {
			$arProperties = @$arFields['PROPERTIES'];
			unset($arFields['PROPERTIES']);
			$o = new static($arFields, $arProperties);
			$o->id = (int) $arFields['ID'];
			return $o;
		}
	}
