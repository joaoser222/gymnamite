import { Grid, Group, Button, Input, Select } from '@mantine/core';
import {
    IconPlus,
    IconTrash,
    IconEye,
    IconEyeOff,
    IconArchive,
    IconSettings,
} from '@tabler/icons-react';
import { useState } from 'react';
import type { VisibilityOption } from '@/types';
import type { TableColumn } from './ServerTable';

export interface ActionToolbarProps {
    columns: TableColumn[];
    onSearchChange?: (field: string, value: string) => void;
    onVisibilityChange?: (value: VisibilityOption) => void;
    onCreate?: () => void;
}

export function ActionToolbar({
    columns,
    onSearchChange,
    onVisibilityChange,
    onCreate,
}: ActionToolbarProps) {
    const searchableColumns = columns
        .filter((c) => c.searchable !== false)
        .map((c) => ({ value: c.key, label: c.title }));

    const visibilityOptions = [
        { value: 'visible', label: 'Visível', icon: IconEye },
        { value: 'hidden', label: 'Oculto', icon: IconEyeOff },
        { value: 'archived', label: 'Arquivado', icon: IconArchive },
    ];

    const [searchField, setSearchField] = useState<string | null>(
        () => searchableColumns[0]?.value ?? null,
    );

    const [searchValue, setSearchValue] = useState<string>('');
    const [visibilityValue, setVisibilityValue] = useState<string>('visible');

    function getActiveColumn() {
        return columns.find((c) => c.key === searchField);
    }

    // Atualiza o campo de pesquisa e limpa o valor
    function handleSearchFieldChange(value: string | null) {
        setSearchField(value);
        setSearchValue('');
        onSearchChange?.(value || '', '');
    }

    // Apenas atualiza o estado local, sem disparar onSearchChange
    function handleSearchValueChange(value: string) {
        setSearchValue(value);
    }

    // Dispara a busca (blur ou Enter)
    function triggerSearch() {
        onSearchChange?.(searchField || '', searchValue);
    }

    function handleKeyDown(e: React.KeyboardEvent<HTMLInputElement>) {
        if (e.key === 'Enter') {
            triggerSearch();
        }
    }

    function handleVisibilityChange(value: string) {
        setVisibilityValue(value);
        onVisibilityChange?.(value as VisibilityOption);
    }

    const activeColumn = getActiveColumn();
    const SearchComponent = activeColumn?.customComponent ?? Input;

    return (
        <Grid gutter="sm">
            <Grid.Col span={{ base: 12 }}>
                <Group justify="right" py="sm">
                    <Button
                        rightSection={<IconEye size={20} />}
                        variant="default"
                    >
                        Alterar visibilidade
                    </Button>
                    <Button
                        rightSection={<IconTrash size={20} />}
                        variant="default"
                        bg="red"
                    >
                        Deletar
                    </Button>
                    <Button
                        rightSection={<IconPlus size={20} />}
                        variant="default"
                        bg="blue"
                        onClick={onCreate}
                    >
                        Adicionar
                    </Button>
                </Group>
            </Grid.Col>
            <Grid.Col span={{ base: 12, sm: 6 }} order={{ base: 2, sm: 1 }}>
                <Button.Group py="sm">
                    {visibilityOptions.map((option) => (
                        <Button
                            key={option.value}
                            rightSection={<option.icon size={20} />}
                            variant="default"
                            bg={
                                option.value === visibilityValue
                                    ? 'dark.5'
                                    : 'dark.7'
                            }
                            onClick={() => handleVisibilityChange(option.value)}
                        >
                            {option.label}
                        </Button>
                    ))}
                </Button.Group>
            </Grid.Col>
            <Grid.Col span={{ base: 12, sm: 6 }} order={{ base: 1, sm: 2 }}>
                <Group justify="right" py="sm">
                    <SearchComponent
                        placeholder="Pesquisar"
                        value={searchValue}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                            handleSearchValueChange(e.target.value)
                        }
                        onBlur={triggerSearch}
                        onKeyDown={handleKeyDown}
                        style={{ minWidth: '200px' }}
                    />
                    <Select
                        data={searchableColumns}
                        value={searchField}
                        onChange={handleSearchFieldChange}
                        rightSection={<IconSettings size={20} />}
                        style={{ width: '150px' }}
                    />
                </Group>
            </Grid.Col>
        </Grid>
    );
}
