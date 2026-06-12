import { Table, Paper, Text,Pagination } from '@mantine/core';

export interface TableColumn {
  key: string;
  title: string;
  sortable?: boolean;
  searchable?: boolean;
  customComponent?: React.ComponentType<React.InputHTMLAttributes<HTMLInputElement>>; 
  width?: string;
  formatter?: (item: any) => any;
  [key: string]: unknown;
}

export interface ServerTableProps {
  columns: TableColumn[];
  page?: number;
  totalPages?: number;
  totalItems?: number;
  items: any[];
  onPageChange?: (page: number) => void;
  onEditRow?: (row: any) => void;
}

export function ServerTable( { columns, items, page = 1, totalPages = 1, totalItems = 0,  onPageChange }: ServerTableProps) {
  const rows = items.map((element, index) => (
    <Table.Tr key={index}>
      {columns.map((column) => (
        <Table.Td key={column.key}>
          {column.formatter?.(element) ?? String(element[column.key as keyof typeof element] ?? '')}
        </Table.Td>
      ))}
    </Table.Tr>
  ));

  return (
    <Paper p="md" withBorder>
      {items.length?
      <>
        <Table.ScrollContainer minWidth={500} maxHeight={300}>
          <Table>
            <Table.Thead>
              <Table.Tr>
                {columns.map((column) => (
                  <Table.Th key={column.key}>{column.title}</Table.Th>
                ))}
              </Table.Tr>
            </Table.Thead>
            <Table.Tbody>{rows}</Table.Tbody>
          </Table>
        </Table.ScrollContainer>
        <Pagination value={page} onChange={onPageChange} total={totalPages} />
      </>
      :
      <Text ta="center" p="lg">Nenhum registro encontrado</Text>
      }
    </Paper>
  );
}